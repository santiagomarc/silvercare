<?php

namespace Tests\Feature;

use App\Models\DoseInstance;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\PrescriptionRevision;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\DoseAdministrationService;
use App\Services\MedicationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Wave 2 — the medication correctness kernel.
 *
 * C5  a dose can no longer be confirmed indefinitely late
 * H1  undo returns exactly what the confirm took, never more
 * H2  a reused idempotency key is a conflict, not a 500
 * H5  hold and skip exist and are respected by every other path
 * H3  editing a prescription records a revision and never rewrites history
 * H4  creating a medication materialises its doses immediately
 */
class DoseKernelHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-08-24 08:15:00', 'Asia/Manila'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @return array{0: User, 1: UserProfile, 2: User, 3: UserProfile} */
    private function createLinkedPair(): array
    {
        $caregiverUser = User::factory()->create(['name' => 'Dana Caregiver']);
        $caregiver = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'danakernel',
            'profile_completed' => true,
        ]);

        $elderlyUser = User::factory()->create(['name' => 'Rosa Senior']);
        $elderly = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'rosakernel',
            'timezone' => 'Asia/Manila',
            'caregiver_id' => $caregiver->id,
            'profile_completed' => true,
        ]);

        return [$elderlyUser, $elderly, $caregiverUser, $caregiver];
    }

    private function createMedication(UserProfile $elderly, array $overrides = []): Medication
    {
        $medication = Medication::create(array_merge([
            'elderly_id' => $elderly->id,
            'name' => 'Amlodipine',
            'dosage' => '5mg',
            'dosage_unit' => 'tablet',
            'frequency' => 'daily',
            'times_of_day' => ['08:00'],
            'start_date' => Carbon::today('Asia/Manila')->toDateString(),
            'is_active' => true,
            'track_inventory' => true,
            'current_stock' => 10,
        ], $overrides));

        MedicationSchedule::create([
            'medication_id' => $medication->id,
            'schedule_type' => 'daily',
            'time_of_day' => '08:00',
        ]);

        return $medication;
    }

    private function todaysDose(UserProfile $elderly, Medication $medication): DoseInstance
    {
        $local = Carbon::parse('2026-08-24 08:00:00', 'Asia/Manila');

        return DoseInstance::create([
            'elderly_id' => $elderly->id,
            'medication_id' => $medication->id,
            'scheduled_at_utc' => $local->copy()->setTimezone('UTC'),
            'local_date' => $local->format('Y-m-d'),
            'timezone' => 'Asia/Manila',
            'state' => 'pending',
            'version' => 1,
        ]);
    }

    private function service(): DoseAdministrationService
    {
        return app(DoseAdministrationService::class);
    }

    // ── C5 ───────────────────────────────────────────────────────────

    public function test_a_dose_far_past_its_window_can_no_longer_be_confirmed(): void
    {
        [, $elderly] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);
        $dose = $this->todaysDose($elderly, $medication);

        // 23:00 — an 08:00 dose is 15 hours late. Previously this recorded as
        // "taken late" and decremented stock.
        Carbon::setTestNow(Carbon::parse('2026-08-24 23:00:00', 'Asia/Manila'));

        $result = $this->service()->confirmDose($dose);

        $this->assertFalse($result['success']);
        $this->assertSame('DOSE_WINDOW_EXPIRED', $result['error_code']);
        $this->assertSame('pending', $dose->fresh()->state);
        $this->assertSame(10, $medication->fresh()->current_stock, 'An expired dose must not touch stock.');
    }

    public function test_a_dose_inside_the_late_bound_still_records_as_taken_late(): void
    {
        [, $elderly] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);
        $dose = $this->todaysDose($elderly, $medication);

        // 11:00 — three hours late, inside the six-hour bound.
        Carbon::setTestNow(Carbon::parse('2026-08-24 11:00:00', 'Asia/Manila'));

        $result = $this->service()->confirmDose($dose);

        $this->assertTrue($result['success']);
        $this->assertSame('taken_late', $result['status']);
        $this->assertSame(9, $medication->fresh()->current_stock);
    }

    public function test_window_evaluation_reports_expiry(): void
    {
        $scheduled = Carbon::parse('2026-08-24 08:00:00', 'Asia/Manila');

        $inGrace = $this->service()->evaluateWindow($scheduled, Carbon::parse('2026-08-24 08:30:00', 'Asia/Manila'));
        $this->assertTrue($inGrace['can_take']);
        $this->assertFalse($inGrace['is_expired']);

        $late = $this->service()->evaluateWindow($scheduled, Carbon::parse('2026-08-24 12:00:00', 'Asia/Manila'));
        $this->assertTrue($late['can_take']);
        $this->assertTrue($late['is_past_window']);
        $this->assertFalse($late['is_expired']);

        $expired = $this->service()->evaluateWindow($scheduled, Carbon::parse('2026-08-24 20:00:00', 'Asia/Manila'));
        $this->assertFalse($expired['can_take'], 'can_take must be bounded, not true forever.');
        $this->assertTrue($expired['is_expired']);
    }

    // ── H1 ───────────────────────────────────────────────────────────

    public function test_undo_does_not_invent_stock_that_was_never_decremented(): void
    {
        [, $elderly] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly, ['current_stock' => 0]);
        $dose = $this->todaysDose($elderly, $medication);

        $confirm = $this->service()->confirmDose($dose);
        $this->assertTrue($confirm['success']);
        $this->assertSame(0, $medication->fresh()->current_stock, 'Nothing to decrement at zero stock.');

        $this->service()->undoDose($dose->fresh());

        $this->assertSame(
            0,
            $medication->fresh()->current_stock,
            'Undo must return only what the confirm actually took — not mint a pill.'
        );
    }

    public function test_undo_returns_exactly_one_unit_when_one_was_taken(): void
    {
        [, $elderly] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly, ['current_stock' => 5]);
        $dose = $this->todaysDose($elderly, $medication);

        $this->service()->confirmDose($dose);
        $this->assertSame(4, $medication->fresh()->current_stock);

        $this->service()->undoDose($dose->fresh());
        $this->assertSame(5, $medication->fresh()->current_stock);
    }

    public function test_repeated_undo_cannot_inflate_stock(): void
    {
        [, $elderly] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly, ['current_stock' => 5]);
        $dose = $this->todaysDose($elderly, $medication);

        $this->service()->confirmDose($dose);
        $this->service()->undoDose($dose->fresh());
        $this->service()->undoDose($dose->fresh());
        $this->service()->undoDose($dose->fresh());

        $this->assertSame(5, $medication->fresh()->current_stock);
    }

    // ── H2 ───────────────────────────────────────────────────────────

    public function test_reusing_an_idempotency_key_on_another_dose_is_a_conflict_not_a_crash(): void
    {
        [, $elderly] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);

        $first = $this->todaysDose($elderly, $medication);
        $second = DoseInstance::create([
            'elderly_id' => $elderly->id,
            'medication_id' => $medication->id,
            'scheduled_at_utc' => Carbon::parse('2026-08-24 20:00:00', 'Asia/Manila')->setTimezone('UTC'),
            'local_date' => '2026-08-24',
            'timezone' => 'Asia/Manila',
            'state' => 'pending',
            'version' => 1,
        ]);

        $this->service()->confirmDose($first, 'senior_ui', null, 'shared-key-123');

        Carbon::setTestNow(Carbon::parse('2026-08-24 20:05:00', 'Asia/Manila'));
        $result = $this->service()->confirmDose($second, 'senior_ui', null, 'shared-key-123');

        $this->assertFalse($result['success']);
        $this->assertSame('IDEMPOTENCY_KEY_REUSED', $result['error_code']);
        $this->assertSame($first->id, $result['conflicting_dose_instance_id']);
    }

    public function test_replaying_the_same_key_on_the_same_dose_is_still_idempotent(): void
    {
        [, $elderly] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly, ['current_stock' => 5]);
        $dose = $this->todaysDose($elderly, $medication);

        $this->service()->confirmDose($dose, 'offline_sync', null, 'retry-key-1');
        $replay = $this->service()->confirmDose($dose->fresh(), 'offline_sync', null, 'retry-key-1');

        $this->assertTrue($replay['success']);
        $this->assertSame(4, $medication->fresh()->current_stock, 'A retry must not decrement twice.');
    }

    public function test_two_patients_can_use_the_same_idempotency_key(): void
    {
        [, $elderlyA] = $this->createLinkedPair();
        $medA = $this->createMedication($elderlyA);
        $doseA = $this->todaysDose($elderlyA, $medA);

        $userB = User::factory()->create();
        $elderlyB = UserProfile::create([
            'user_id' => $userB->id,
            'user_type' => 'elderly',
            'username' => 'secondsenior',
            'timezone' => 'Asia/Manila',
            'profile_completed' => true,
        ]);
        $medB = $this->createMedication($elderlyB);
        $doseB = $this->todaysDose($elderlyB, $medB);

        $this->assertTrue($this->service()->confirmDose($doseA, 'senior_ui', null, 'client-uuid-1')['success']);

        // Two different phones can generate the same key; it must not collide.
        $this->assertTrue($this->service()->confirmDose($doseB, 'senior_ui', null, 'client-uuid-1')['success']);
    }

    // ── H5 ───────────────────────────────────────────────────────────

    public function test_caregiver_can_hold_a_dose_and_it_never_becomes_missed(): void
    {
        [, $elderly, $caregiverUser] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);
        $dose = $this->todaysDose($elderly, $medication);

        $result = $this->service()->holdDose($dose, $caregiverUser->id, 'Patient is NPO before surgery');

        $this->assertTrue($result['success']);
        $this->assertSame('held', $dose->fresh()->state);
        $this->assertSame('Patient is NPO before surgery', $dose->fresh()->state_reason);

        // The scheduler sweep must leave a clinical decision alone.
        $this->service()->markMissed($dose->fresh());
        $this->assertSame('held', $dose->fresh()->state);
        $this->assertDatabaseMissing('alerts', ['source_type' => 'missed_dose']);
    }

    public function test_a_held_dose_cannot_be_silently_confirmed(): void
    {
        [, $elderly, $caregiverUser] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);
        $dose = $this->todaysDose($elderly, $medication);

        $this->service()->holdDose($dose, $caregiverUser->id, 'NPO');

        $result = $this->service()->confirmDose($dose->fresh());

        $this->assertFalse($result['success']);
        $this->assertSame('DOSE_HELD', $result['error_code']);
        $this->assertSame(10, $medication->fresh()->current_stock);
    }

    public function test_senior_can_skip_a_dose_with_a_reason(): void
    {
        [$elderlyUser, $elderly] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);
        $dose = $this->todaysDose($elderly, $medication);

        $this->actingAs($elderlyUser)
            ->postJson(route('doses.skip', $dose), ['reason' => 'Felt nauseous'])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'skipped']);

        $this->assertSame('skipped', $dose->fresh()->state);
        $this->assertSame('Felt nauseous', $dose->fresh()->state_reason);
    }

    public function test_skipping_requires_a_reason(): void
    {
        [$elderlyUser, $elderly] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);
        $dose = $this->todaysDose($elderly, $medication);

        $this->actingAs($elderlyUser)
            ->postJson(route('doses.skip', $dose), ['reason' => ''])
            ->assertStatus(422);
    }

    public function test_a_senior_cannot_hold_their_own_dose(): void
    {
        [$elderlyUser, $elderly] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);
        $dose = $this->todaysDose($elderly, $medication);

        // Holding is a clinical instruction, not a patient decision. The
        // caregiver middleware turns the senior away before the controller's
        // own ownership check runs, so the response is a redirect rather than
        // a 403 — either way the dose must be untouched.
        $response = $this->actingAs($elderlyUser)
            ->postJson(route('caregiver.doses.hold', $dose), ['reason' => 'I do not want it']);

        $this->assertContains($response->getStatusCode(), [302, 403]);
        $this->assertSame('pending', $dose->fresh()->state);
        $this->assertNull($dose->fresh()->state_reason);
    }

    public function test_an_already_taken_dose_cannot_be_held(): void
    {
        [, $elderly, $caregiverUser] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);
        $dose = $this->todaysDose($elderly, $medication);

        $this->service()->confirmDose($dose);

        $result = $this->service()->holdDose($dose->fresh(), $caregiverUser->id, 'NPO');

        $this->assertFalse($result['success']);
        $this->assertSame('DOSE_ALREADY_TAKEN', $result['error_code']);
    }

    // ── H3 / H4 ──────────────────────────────────────────────────────

    public function test_creating_a_medication_generates_its_doses_immediately(): void
    {
        [, $elderly, $caregiverUser, $caregiver] = $this->createLinkedPair();

        app(MedicationService::class)->addMedicationSchedule([
            'elderly_id' => $elderly->id,
            'caregiver_id' => $caregiver->id,
            'name' => 'Metformin',
            'dosage' => '500mg',
            'times_of_day' => ['20:00'],
            'start_date' => Carbon::today('Asia/Manila')->toDateString(),
        ]);

        $this->assertGreaterThan(
            0,
            DoseInstance::where('elderly_id', $elderly->id)->count(),
            'A medication added at 08:15 must not be invisible until the next midnight job.'
        );
    }

    public function test_editing_a_schedule_records_a_revision_and_leaves_history_alone(): void
    {
        [, $elderly, $caregiverUser, $caregiver] = $this->createLinkedPair();

        $medication = app(MedicationService::class)->addMedicationSchedule([
            'elderly_id' => $elderly->id,
            'caregiver_id' => $caregiver->id,
            'name' => 'Metformin',
            'dosage' => '500mg',
            'times_of_day' => ['08:00'],
            'start_date' => Carbon::today('Asia/Manila')->toDateString(),
        ]);

        // A dose from this morning that already happened.
        $taken = DoseInstance::where('medication_id', $medication->id)
            ->where('local_date', '2026-08-24')
            ->firstOrFail();
        $taken->update(['state' => 'taken', 'taken_at' => Carbon::now()]);

        $this->actingAs($caregiverUser);

        app(MedicationService::class)->updateMedicationSchedule($medication, [
            'times_of_day' => ['09:00'],
            'dosage' => '1000mg',
        ]);

        $revision = PrescriptionRevision::where('medication_id', $medication->id)->first();
        $this->assertNotNull($revision, 'A schedule edit must leave an audit record.');
        $this->assertSame('500mg', $revision->old_values['dosage']);
        $this->assertSame('1000mg', $revision->new_values['dosage']);
        $this->assertSame($caregiverUser->id, $revision->changed_by);

        // History survives the edit untouched.
        $this->assertSame('taken', $taken->fresh()->state);

        // Future doses follow the new schedule.
        $future = DoseInstance::where('medication_id', $medication->id)
            ->where('state', 'pending')
            ->where('scheduled_at_utc', '>', Carbon::now())
            ->get();

        $this->assertGreaterThan(0, $future->count());
        foreach ($future as $instance) {
            $this->assertSame(
                '09:00',
                $instance->scheduled_at_utc->copy()->setTimezone('Asia/Manila')->format('H:i'),
                'Regenerated doses must use the edited time.'
            );
        }
    }

    public function test_an_edit_that_changes_nothing_records_no_revision(): void
    {
        [, $elderly, $caregiverUser, $caregiver] = $this->createLinkedPair();

        $medication = app(MedicationService::class)->addMedicationSchedule([
            'elderly_id' => $elderly->id,
            'caregiver_id' => $caregiver->id,
            'name' => 'Metformin',
            'dosage' => '500mg',
            'times_of_day' => ['08:00'],
            'start_date' => Carbon::today('Asia/Manila')->toDateString(),
        ]);

        $this->actingAs($caregiverUser);
        app(MedicationService::class)->updateMedicationSchedule($medication, ['dosage' => '500mg']);

        $this->assertSame(0, PrescriptionRevision::where('medication_id', $medication->id)->count());
    }
}
