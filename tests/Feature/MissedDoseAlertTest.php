<?php

namespace Tests\Feature;

use App\Mail\ClinicalAlertMail;
use App\Models\Alert;
use App\Models\DoseInstance;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\DoseAdministrationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * C4 — missed doses must reach a caregiver.
 *
 * markMissed() previously flipped the dose state and wrote one patient-facing
 * notification. It created no Alert, queued no delivery and set no escalation
 * deadline, so the plan's own example — "2 consecutive metformin doses missed"
 * — could never fire. source_type 'missed_dose' was never produced anywhere.
 *
 * H7 is covered here too: the alert email must be queued, never sent inline.
 */
class MissedDoseAlertTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: UserProfile, 2: UserProfile} */
    private function createLinkedPair(): array
    {
        $caregiverUser = User::factory()->create(['name' => 'Dana Caregiver']);
        $caregiver = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'danacare',
            'profile_completed' => true,
        ]);

        $elderlyUser = User::factory()->create(['name' => 'Rosa Senior']);
        $elderly = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'rosasenior',
            'timezone' => 'Asia/Manila',
            'caregiver_id' => $caregiver->id,
            'profile_completed' => true,
        ]);

        return [$elderlyUser, $elderly, $caregiver];
    }

    private function createMedication(UserProfile $elderly): Medication
    {
        $medication = Medication::create([
            'elderly_id' => $elderly->id,
            'name' => 'Metformin',
            'dosage' => '500mg',
            'dosage_unit' => 'tablet',
            'frequency' => 'daily',
            'times_of_day' => ['08:00'],
            'is_active' => true,
            'track_inventory' => false,
        ]);

        MedicationSchedule::create([
            'medication_id' => $medication->id,
            'schedule_type' => 'daily',
            'time_of_day' => '08:00',
        ]);

        return $medication;
    }

    /**
     * Build a dose instance $daysAgo days back at 08:00 Manila.
     */
    private function makeDose(UserProfile $elderly, Medication $medication, int $daysAgo, string $state = 'pending'): DoseInstance
    {
        $local = Carbon::parse('2026-08-20 08:00:00', 'Asia/Manila')->subDays($daysAgo);

        return DoseInstance::create([
            'elderly_id' => $elderly->id,
            'medication_id' => $medication->id,
            'scheduled_at_utc' => $local->copy()->setTimezone('UTC'),
            'local_date' => $local->format('Y-m-d'),
            'timezone' => 'Asia/Manila',
            'state' => $state,
            'taken_at' => in_array($state, ['taken', 'taken_late'], true) ? $local : null,
            'version' => 1,
        ]);
    }

    private function doseService(): DoseAdministrationService
    {
        return app(DoseAdministrationService::class);
    }

    public function test_single_missed_dose_notifies_patient_but_does_not_alert_caregiver(): void
    {
        Mail::fake();
        [, $elderly, $caregiver] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);

        $dose = $this->makeDose($elderly, $medication, 0);
        $this->doseService()->markMissed($dose);

        $this->assertDatabaseHas('medication_dose_instances', [
            'id' => $dose->id,
            'state' => 'missed',
        ]);

        // The senior still gets their reminder…
        $this->assertDatabaseHas('notifications', [
            'elderly_id' => $elderly->id,
            'type' => 'medication_missed',
        ]);

        // …but one miss is noise, not a caregiver page.
        $this->assertDatabaseMissing('alerts', ['source_type' => 'missed_dose']);
        Mail::assertNothingQueued();
    }

    public function test_two_consecutive_misses_raise_a_warning_alert_with_deliveries(): void
    {
        Mail::fake();
        [, $elderly, $caregiver] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);

        // Oldest first, the order the scheduler sweeps in.
        $this->doseService()->markMissed($this->makeDose($elderly, $medication, 1));
        $this->doseService()->markMissed($this->makeDose($elderly, $medication, 0));

        $alert = Alert::where('source_type', 'missed_dose')->first();

        $this->assertNotNull($alert, 'A caregiver alert should exist after two consecutive misses.');
        $this->assertSame('warning', $alert->severity);
        $this->assertSame('open', $alert->state);
        $this->assertSame(2, $alert->metadata['consecutive_missed']);
        $this->assertSame($medication->id, $alert->metadata['medication_id']);
        $this->assertStringContainsString('Metformin', $alert->title);
        $this->assertNotNull($alert->escalate_at);

        // Delivered in-app to both parties, and no delivery may be left failed.
        $this->assertDatabaseHas('alert_deliveries', [
            'alert_id' => $alert->id,
            'recipient_profile_id' => $caregiver->id,
            'channel' => 'in_app',
            'state' => 'delivered',
        ]);
        $this->assertDatabaseMissing('alert_deliveries', [
            'alert_id' => $alert->id,
            'state' => 'failed',
        ]);
    }

    public function test_third_consecutive_miss_escalates_to_critical_and_queues_email(): void
    {
        Mail::fake();
        [, $elderly, $caregiver] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);

        $this->doseService()->markMissed($this->makeDose($elderly, $medication, 2));
        $this->doseService()->markMissed($this->makeDose($elderly, $medication, 1));

        // Warning only so far — no email at this severity.
        Mail::assertNothingQueued();

        $this->doseService()->markMissed($this->makeDose($elderly, $medication, 0));

        $alerts = Alert::where('source_type', 'missed_dose')->get();
        $this->assertCount(1, $alerts, 'A continuing run must update one alert, not stack new ones.');

        $alert = $alerts->first();
        $this->assertSame('critical', $alert->severity);
        $this->assertSame(3, $alert->metadata['consecutive_missed']);

        // H7: escalation email goes to the queue, never inline.
        Mail::assertQueued(ClinicalAlertMail::class, function (ClinicalAlertMail $mail) use ($alert) {
            return $mail->alert->id === $alert->id
                && $mail->patientName === 'Rosa Senior';
        });
        Mail::assertNotSent(ClinicalAlertMail::class);
    }

    public function test_escalation_reopens_an_acknowledged_alert(): void
    {
        Mail::fake();
        [, $elderly, $caregiver] = $this->createLinkedPair();
        $caregiverUser = $caregiver->user;
        $medication = $this->createMedication($elderly);

        $this->doseService()->markMissed($this->makeDose($elderly, $medication, 2));
        $this->doseService()->markMissed($this->makeDose($elderly, $medication, 1));

        $alert = Alert::where('source_type', 'missed_dose')->firstOrFail();
        $alert->acknowledge($caregiverUser->id);
        $this->assertSame('acknowledged', $alert->fresh()->state);

        // The caregiver acknowledged two misses, not three.
        $this->doseService()->markMissed($this->makeDose($elderly, $medication, 0));

        $alert->refresh();
        $this->assertSame('open', $alert->state);
        $this->assertSame('critical', $alert->severity);
        $this->assertNull($alert->acknowledged_at);
        $this->assertNull($alert->acknowledged_by);
    }

    public function test_a_taken_dose_breaks_the_run(): void
    {
        Mail::fake();
        [, $elderly, $caregiver] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);

        $this->doseService()->markMissed($this->makeDose($elderly, $medication, 3));
        $this->makeDose($elderly, $medication, 2, 'taken');
        $this->doseService()->markMissed($this->makeDose($elderly, $medication, 1));

        // Only one miss since the taken dose — below the threshold.
        $this->assertDatabaseMissing('alerts', ['source_type' => 'missed_dose']);

        $this->doseService()->markMissed($this->makeDose($elderly, $medication, 0));

        $alert = Alert::where('source_type', 'missed_dose')->first();
        $this->assertNotNull($alert);
        $this->assertSame(2, $alert->metadata['consecutive_missed']);
    }

    public function test_a_different_medications_misses_do_not_combine(): void
    {
        Mail::fake();
        [, $elderly, $caregiver] = $this->createLinkedPair();

        $metformin = $this->createMedication($elderly);
        $amlodipine = Medication::create([
            'elderly_id' => $elderly->id,
            'name' => 'Amlodipine',
            'dosage' => '5mg',
            'dosage_unit' => 'tablet',
            'frequency' => 'daily',
            'times_of_day' => ['08:00'],
            'is_active' => true,
            'track_inventory' => false,
        ]);

        $this->doseService()->markMissed($this->makeDose($elderly, $metformin, 1));
        $this->doseService()->markMissed($this->makeDose($elderly, $amlodipine, 0));

        // One miss each — neither reaches the threshold.
        $this->assertDatabaseMissing('alerts', ['source_type' => 'missed_dose']);
    }

    public function test_marking_missed_is_idempotent(): void
    {
        Mail::fake();
        [, $elderly, $caregiver] = $this->createLinkedPair();
        $medication = $this->createMedication($elderly);

        $first = $this->makeDose($elderly, $medication, 1);
        $second = $this->makeDose($elderly, $medication, 0);

        $this->doseService()->markMissed($first);
        $this->doseService()->markMissed($second);
        // Re-running the sweep must not inflate the run or duplicate the alert.
        $this->doseService()->markMissed($first->fresh());
        $this->doseService()->markMissed($second->fresh());

        $this->assertCount(1, Alert::where('source_type', 'missed_dose')->get());
        $this->assertSame(
            2,
            Alert::where('source_type', 'missed_dose')->first()->metadata['consecutive_missed']
        );
    }
}
