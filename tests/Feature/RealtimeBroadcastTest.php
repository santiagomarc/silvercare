<?php

namespace Tests\Feature;

use App\Events\AlertStatusUpdated;
use App\Events\CheckinReceivedEvent;
use App\Events\CriticalAlertFired;
use App\Events\DoseConfirmedEvent;
use App\Models\Alert;
use App\Models\CareCheckin;
use App\Models\Medication;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\CareCheckinService;
use App\Services\DoseAdministrationService;
use App\Services\DoseInstanceGeneratorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeBroadcastTest extends TestCase
{
    use RefreshDatabase;

    /**
     * These tests confirm an 08:00 dose. Without a frozen clock the result
     * depended on the wall-clock hour the suite happened to run at — after the
     * dose window's outer bound the confirm is correctly refused (C5). Pin the
     * time so the test asserts behaviour, not the time of day.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-24 08:15:00', 'Asia/Manila'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createLinkedPair(): array
    {
        $caregiverUser = User::factory()->create(['name' => 'Dr. Caregiver']);
        $caregiverProfile = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'drcaregiver',
            'profile_completed' => true,
        ]);

        $elderlyUser = User::factory()->create(['name' => 'Alice Senior']);
        $elderlyProfile = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'alicesenior',
            'caregiver_id' => $caregiverProfile->id,
            'timezone' => 'Asia/Manila',
            'profile_completed' => true,
        ]);

        return [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile];
    }

    public function test_dose_confirmation_broadcasts_dose_confirmed_event(): void
    {
        Event::fake([DoseConfirmedEvent::class]);
        [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile] = $this->createLinkedPair();

        $medication = Medication::create([
            'elderly_id' => $elderlyProfile->id,
            'name' => 'Metformin',
            'dosage' => '500mg',
            'times_of_day' => ['08:00'],
            'start_date' => Carbon::today('Asia/Manila')->toDateString(),
            'current_stock' => 30,
            'track_inventory' => true,
        ]);

        app(DoseInstanceGeneratorService::class)->generateForMedication($medication, Carbon::today('Asia/Manila'), 7);

        app(DoseAdministrationService::class)->confirmDoseByMedicationAndTime(
            $medication,
            $elderlyProfile->id,
            '08:00',
            'senior_ui',
            $elderlyUser->id
        );

        Event::assertDispatched(DoseConfirmedEvent::class, function ($event) use ($caregiverProfile) {
            return $event->recipientCaregiverProfileId === $caregiverProfile->id;
        });
    }

    public function test_checkin_broadcasts_checkin_received_event(): void
    {
        Event::fake([CheckinReceivedEvent::class]);
        [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile] = $this->createLinkedPair();

        app(CareCheckinService::class)->recordCheckin($elderlyProfile, 'ok', 'Feeling great');

        Event::assertDispatched(CheckinReceivedEvent::class, function ($event) use ($caregiverProfile) {
            return $event->recipientCaregiverProfileId === $caregiverProfile->id && $event->checkin->status === 'ok';
        });
    }

    public function test_alert_acknowledge_and_resolve_broadcasts_status_updated(): void
    {
        Event::fake([AlertStatusUpdated::class]);
        [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile] = $this->createLinkedPair();

        $alert = Alert::create([
            'elderly_id' => $elderlyProfile->id,
            'severity' => 'critical',
            'source_type' => 'vital_threshold',
            'title' => 'High BP',
            'message' => 'BP 185/120',
            'state' => 'open',
        ]);

        // Acknowledge
        $this->actingAs($caregiverUser)->postJson(route('caregiver.alerts.acknowledge', $alert));
        Event::assertDispatched(AlertStatusUpdated::class);

        // Resolve
        $this->actingAs($caregiverUser)->postJson(route('caregiver.alerts.resolve', $alert));
        Event::assertDispatched(AlertStatusUpdated::class);
    }
}
