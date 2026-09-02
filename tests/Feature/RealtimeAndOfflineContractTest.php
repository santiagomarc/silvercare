<?php

namespace Tests\Feature;

use App\Events\DoseConfirmedEvent;
use App\Models\Alert;
use App\Models\DoseInstance;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AlertDeliveryService;
use App\Services\DoseAdministrationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Wave 3 — the server half of the frontend contract.
 *
 * The browser behaviour (amber pending badge, Echo subscription) can only be
 * proven in a real browser. What can be pinned here is the contract the
 * frontend depends on:
 *
 *  C2  the HTTP status codes the offline queue uses to tell a conflict from a
 *      permanent rejection, and the idempotency header that makes replay safe
 *  C3  the channels a dose broadcast reaches, and the fact that dispatching to
 *      Reverb is recorded as 'sent' rather than claimed as 'delivered'
 */
class RealtimeAndOfflineContractTest extends TestCase
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

    /**
     * Swap the null broadcaster for a real one so channel callbacks actually run.
     *
     * Two things make this necessary. NullBroadcaster::auth() is an empty method
     * that authorises every private channel, so the default test driver proves
     * nothing about authorisation. And Broadcast::channel() registers callbacks
     * on whichever driver is current at boot — switching drivers afterwards
     * leaves the new one with no channels at all, so routes/channels.php has to
     * be re-run against it.
     */
    private function useRealBroadcaster(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);

        require base_path('routes/channels.php');
    }

    /** @return array{0: User, 1: UserProfile, 2: User, 3: UserProfile} */
    private function createLinkedPair(): array
    {
        $caregiverUser = User::factory()->create(['name' => 'Dana Caregiver']);
        $caregiver = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'danawave3',
            'profile_completed' => true,
        ]);

        $elderlyUser = User::factory()->create(['name' => 'Rosa Senior']);
        $elderly = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'rosawave3',
            'timezone' => 'Asia/Manila',
            'caregiver_id' => $caregiver->id,
            'profile_completed' => true,
        ]);

        return [$elderlyUser, $elderly, $caregiverUser, $caregiver];
    }

    private function createMedication(UserProfile $elderly): Medication
    {
        $medication = Medication::create([
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
        ]);

        MedicationSchedule::create([
            'medication_id' => $medication->id,
            'schedule_type' => 'daily',
            'time_of_day' => '08:00',
        ]);

        return $medication;
    }

    private function dose(UserProfile $elderly, Medication $medication): DoseInstance
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

    // ── C2: the offline queue's contract ─────────────────────────────

    public function test_replaying_a_queued_dose_with_its_idempotency_key_applies_once(): void
    {
        [$elderlyUser, $elderly] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);

        $headers = ['X-Idempotency-Key' => 'queued-intent-abc'];

        $this->actingAs($elderlyUser)
            ->postJson(route('elderly.medications.take', $medication), ['time' => '08:00'], $headers)
            ->assertOk();

        // The queue retries the identical request after reconnecting.
        $this->actingAs($elderlyUser)
            ->postJson(route('elderly.medications.take', $medication), ['time' => '08:00'], $headers)
            ->assertOk();

        $this->assertSame(
            9,
            $medication->fresh()->current_stock,
            'A replayed offline intent must decrement stock once, not twice.'
        );
    }

    public function test_a_held_dose_returns_409_so_the_queue_flags_it_for_review(): void
    {
        [$elderlyUser, $elderly, $caregiverUser] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);
        $dose = $this->dose($elderly, $medication);

        app(DoseAdministrationService::class)->holdDose($dose, $caregiverUser->id, 'NPO before surgery');

        // 409, not 400: the offline queue treats a conflict as "needs review"
        // and a plain 4xx as "permanently rejected, discard".
        $this->actingAs($elderlyUser)
            ->postJson(route('elderly.medications.take', $medication), ['time' => '08:00'])
            ->assertStatus(409)
            ->assertJson(['error_code' => 'DOSE_HELD']);
    }

    public function test_an_expired_dose_returns_422_not_a_conflict(): void
    {
        [$elderlyUser, $elderly] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);
        $this->dose($elderly, $medication);

        Carbon::setTestNow(Carbon::parse('2026-08-24 23:00:00', 'Asia/Manila'));

        $this->actingAs($elderlyUser)
            ->postJson(route('elderly.medications.take', $medication), ['time' => '08:00'])
            ->assertStatus(422)
            ->assertJson(['error_code' => 'DOSE_WINDOW_EXPIRED']);
    }

    // ── C3: the realtime contract ────────────────────────────────────

    public function test_a_dose_broadcast_reaches_both_the_caregiver_and_the_senior(): void
    {
        [, $elderly, , $caregiver] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);
        $dose = $this->dose($elderly, $medication);

        $event = new DoseConfirmedEvent($dose, $caregiver->id);

        $channels = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains("private-caregiver.{$caregiver->id}", $channels);
        $this->assertContains(
            "private-elderly.{$elderly->id}",
            $channels,
            'The senior needs their own channel so a dose logged elsewhere updates their screen.'
        );
    }

    public function test_the_dose_broadcast_carries_what_the_tracker_needs(): void
    {
        [, $elderly, , $caregiver] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);
        $dose = $this->dose($elderly, $medication);

        app(DoseAdministrationService::class)->confirmDose($dose);

        $payload = (new DoseConfirmedEvent($dose->fresh(), $caregiver->id))->broadcastWith();

        $this->assertSame($medication->id, $payload['medication_id']);
        $this->assertSame('08:00', $payload['scheduled_time']);
        $this->assertArrayHasKey('taken_late', $payload);
    }

    /**
     * Channel authorisation must be exercised against a real broadcaster.
     *
     * The suite runs with BROADCAST_CONNECTION=null, and NullBroadcaster::auth()
     * is an empty method — it authorises every private channel without ever
     * calling the callbacks in routes/channels.php. Testing this through the
     * default driver would pass no matter what those callbacks said.
     */
    public function test_the_elderly_channel_only_authorises_the_patient_themselves(): void
    {
        [$elderlyUser, $elderly, $caregiverUser] = $this->createLinkedPair();

        $this->useRealBroadcaster();

        // The patient may listen to their own channel…
        $this->actingAs($elderlyUser)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-elderly.{$elderly->id}",
                'socket_id' => '123.456',
            ])
            ->assertOk();

        // …their caregiver may not. Dose traffic is not part of the alert path,
        // and a caregiver listening here would receive PHI outside it.
        $this->actingAs($caregiverUser)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-elderly.{$elderly->id}",
                'socket_id' => '123.456',
            ])
            ->assertForbidden();
    }

    public function test_the_caregiver_channel_rejects_an_unrelated_caregiver(): void
    {
        [, , , $caregiver] = $this->createLinkedPair();

        $otherUser = User::factory()->create();
        UserProfile::create([
            'user_id' => $otherUser->id,
            'user_type' => 'caregiver',
            'username' => 'unrelatedcarer',
            'profile_completed' => true,
        ]);

        $this->useRealBroadcaster();

        $this->actingAs($otherUser)
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-caregiver.{$caregiver->id}",
                'socket_id' => '123.456',
            ])
            ->assertForbidden();
    }

    public function test_reverb_dispatch_is_recorded_as_sent_not_delivered(): void
    {
        [, $elderly, , $caregiver] = $this->createLinkedPair();

        $alert = Alert::create([
            'elderly_id' => $elderly->id,
            'severity' => 'critical',
            'source_type' => 'vital_threshold',
            'title' => 'Critical reading',
            'message' => 'A critical reading was recorded.',
            'state' => 'open',
        ]);

        app(AlertDeliveryService::class)->deliver($alert);

        // Handing an event to Reverb proves it left the server, not that a
        // browser received it. Claiming 'delivered' would put a falsehood in
        // the clinical audit trail.
        $this->assertDatabaseHas('alert_deliveries', [
            'alert_id' => $alert->id,
            'channel' => 'reverb',
            'state' => 'sent',
        ]);

        $this->assertDatabaseMissing('alert_deliveries', [
            'alert_id' => $alert->id,
            'channel' => 'reverb',
            'state' => 'delivered',
        ]);
    }
}
