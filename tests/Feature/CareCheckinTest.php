<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\CareCheckin;
use App\Models\PatientAlertThreshold;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\ClinicalInsightService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareCheckinTest extends TestCase
{
    use RefreshDatabase;

    private function createLinkedPair(): array
    {
        $caregiverUser = User::factory()->create(['name' => 'Dr. Caregiver', 'email' => 'caregiver@example.com']);
        $caregiverProfile = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'drcaregiver',
            'profile_completed' => true,
        ]);

        $elderlyUser = User::factory()->create(['name' => 'Alice Senior', 'email' => 'alice@example.com']);
        $elderlyProfile = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'alicesenior',
            'caregiver_id' => $caregiverProfile->id,
            'profile_completed' => true,
        ]);

        return [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile];
    }

    public function test_elderly_can_record_daily_im_ok_checkin(): void
    {
        [$elderlyUser, $elderlyProfile] = $this->createLinkedPair();

        $response = $this->actingAs($elderlyUser)
            ->postJson(route('elderly.checkin'), [
                'status' => 'ok',
                'notes' => 'Feeling great this morning',
                'mood' => 'great',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'You are checked in for today! Great job.',
            ]);

        $checkin = CareCheckin::where('elderly_id', $elderlyProfile->id)->first();
        $this->assertNotNull($checkin);
        $this->assertEquals('ok', $checkin->status);
        $this->assertEquals('great', $checkin->mood);
        $this->assertEquals(Carbon::today()->toDateString(), $checkin->checkin_date->toDateString());

        $this->assertTrue($elderlyProfile->fresh()->hasCheckedInToday());
    }

    public function test_checkin_with_need_help_creates_caregiver_alert(): void
    {
        [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile] = $this->createLinkedPair();

        $response = $this->actingAs($elderlyUser)
            ->postJson(route('elderly.checkin'), [
                'status' => 'need_help',
                'notes' => 'I feel very dizzy today',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Help request sent to your caregiver.',
            ]);

        $this->assertDatabaseHas('alerts', [
            'elderly_id' => $elderlyProfile->id,
            'severity' => 'warning',
            'source_type' => 'check_in_need_help',
            'state' => 'open',
        ]);
    }

    public function test_caregiver_can_view_patient_checkin_history(): void
    {
        [$elderlyUser, $elderlyProfile, $caregiverUser] = $this->createLinkedPair();

        CareCheckin::create([
            'elderly_id' => $elderlyProfile->id,
            'checkin_date' => Carbon::yesterday()->toDateString(),
            'status' => 'ok',
            'checked_in_at' => Carbon::yesterday(),
        ]);

        $response = $this->actingAs($caregiverUser)
            ->getJson(route('caregiver.patients.checkins', $elderlyProfile));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'patient_id',
                'checkins',
            ]);
    }

    public function test_clinical_insight_service_calculates_risk_score_and_data_freshness(): void
    {
        [$elderlyUser, $elderlyProfile] = $this->createLinkedPair();

        $service = app(ClinicalInsightService::class);
        $briefing = $service->getDailyBriefing($elderlyProfile);

        $this->assertArrayHasKey('risk', $briefing);
        $this->assertArrayHasKey('freshness', $briefing);
        $this->assertArrayHasKey('medication_adherence', $briefing);
        $this->assertArrayHasKey('highlights', $briefing);
        $this->assertIsInt($briefing['risk']['score']);
        $this->assertContains($briefing['risk']['level'], ['low', 'moderate', 'high']);
    }

    public function test_caregiver_can_get_and_update_patient_alert_thresholds(): void
    {
        [$elderlyUser, $elderlyProfile, $caregiverUser] = $this->createLinkedPair();

        // 1. Get thresholds
        $getRes = $this->actingAs($caregiverUser)
            ->getJson(route('caregiver.patients.thresholds.index', $elderlyProfile));

        $getRes->assertOk()
            ->assertJsonStructure(['success', 'patient_id', 'metrics']);

        // 2. Update custom threshold
        $updateRes = $this->actingAs($caregiverUser)
            ->postJson(route('caregiver.patients.thresholds.update', $elderlyProfile), [
                'metric_type' => 'blood_pressure',
                'thresholds' => [
                    'warning_systolic_high' => 135,
                    'warning_diastolic_high' => 88,
                ],
            ]);

        $updateRes->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('patient_alert_thresholds', [
            'elderly_id' => $elderlyProfile->id,
            'metric_type' => 'blood_pressure',
        ]);
    }

    public function test_check_missed_checkins_command_runs_successfully(): void
    {
        [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile] = $this->createLinkedPair();

        $this->artisan('checkins:check-missed')
            ->assertSuccessful();
    }
}
