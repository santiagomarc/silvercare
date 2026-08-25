<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\HealthMetric;
use App\Models\PatientAlertThreshold;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\ClinicalRulesService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalAlertTest extends TestCase
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

    public function test_critical_blood_pressure_triggers_critical_alert_and_deliveries(): void
    {
        [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile] = $this->createLinkedPair();

        $response = $this->actingAs($elderlyUser)
            ->postJson(route('elderly.vitals.store'), [
                'type' => 'blood_pressure',
                'value_text' => '185/125',
                'notes' => 'Feeling dizzy',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'alert_triggered' => true,
                'alert_severity' => 'critical',
            ]);

        $this->assertDatabaseHas('alerts', [
            'elderly_id' => $elderlyProfile->id,
            'severity' => 'critical',
            'source_type' => 'vital_threshold',
            'state' => 'open',
        ]);

        $alert = Alert::where('elderly_id', $elderlyProfile->id)->latest()->first();
        $this->assertNotNull($alert);

        $this->assertDatabaseHas('alert_deliveries', [
            'alert_id' => $alert->id,
            'recipient_profile_id' => $caregiverProfile->id,
            'channel' => 'in_app',
            'state' => 'delivered',
        ]);
    }

    public function test_elevated_blood_pressure_triggers_warning_alert(): void
    {
        [$elderlyUser, $elderlyProfile] = $this->createLinkedPair();

        $response = $this->actingAs($elderlyUser)
            ->postJson(route('elderly.vitals.store'), [
                'type' => 'blood_pressure',
                'value_text' => '145/95',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'alert_triggered' => true,
                'alert_severity' => 'warning',
            ]);

        $this->assertDatabaseHas('alerts', [
            'elderly_id' => $elderlyProfile->id,
            'severity' => 'warning',
            'state' => 'open',
        ]);
    }

    public function test_normal_vital_does_not_trigger_alert(): void
    {
        [$elderlyUser, $elderlyProfile] = $this->createLinkedPair();

        $response = $this->actingAs($elderlyUser)
            ->postJson(route('elderly.vitals.store'), [
                'type' => 'blood_pressure',
                'value_text' => '118/76',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'alert_triggered' => false,
            ]);

        $this->assertDatabaseMissing('alerts', [
            'elderly_id' => $elderlyProfile->id,
        ]);
    }

    public function test_custom_patient_threshold_override_takes_precedence(): void
    {
        [$elderlyUser, $elderlyProfile] = $this->createLinkedPair();

        // Configure custom tighter warning threshold for this patient: warning at 130 instead of 140
        PatientAlertThreshold::create([
            'elderly_id' => $elderlyProfile->id,
            'metric_type' => 'blood_pressure',
            'thresholds' => [
                'warning_systolic_high' => 130,
                'warning_diastolic_high' => 85,
            ],
        ]);

        $response = $this->actingAs($elderlyUser)
            ->postJson(route('elderly.vitals.store'), [
                'type' => 'blood_pressure',
                'value_text' => '135/80',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'alert_triggered' => true,
                'alert_severity' => 'warning',
            ]);
    }

    public function test_sos_triggers_emergency_alert_and_returns_emergency_notice(): void
    {
        [$elderlyUser, $elderlyProfile] = $this->createLinkedPair();

        $response = $this->actingAs($elderlyUser)
            ->postJson(route('elderly.sos'), [
                'notes' => 'I fell in the kitchen',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'SOS alert sent to your caregiver!',
            ])
            ->assertJsonStructure(['emergency_notice']);

        $this->assertDatabaseHas('alerts', [
            'elderly_id' => $elderlyProfile->id,
            'severity' => 'emergency',
            'source_type' => 'sos',
            'state' => 'open',
        ]);
    }

    public function test_caregiver_can_list_and_acknowledge_alert(): void
    {
        [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile] = $this->createLinkedPair();

        $alert = Alert::create([
            'elderly_id' => $elderlyProfile->id,
            'severity' => 'critical',
            'source_type' => 'vital_threshold',
            'title' => 'Critical BP',
            'message' => 'BP is 190/130',
            'state' => 'open',
            'escalate_at' => Carbon::now()->addMinutes(15),
        ]);

        // Caregiver lists alerts
        $listRes = $this->actingAs($caregiverUser)
            ->getJson(route('caregiver.alerts.index'));

        $listRes->assertOk()
            ->assertJsonStructure(['success', 'alerts']);

        // Caregiver acknowledges alert
        $ackRes = $this->actingAs($caregiverUser)
            ->postJson(route('caregiver.alerts.acknowledge', $alert));

        $ackRes->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Alert acknowledged successfully.',
            ]);

        $this->assertEquals('acknowledged', $alert->fresh()->state);
        $this->assertNotNull($alert->fresh()->acknowledged_at);
        $this->assertEquals($caregiverUser->id, $alert->fresh()->acknowledged_by);
    }

    public function test_caregiver_can_resolve_alert(): void
    {
        [$elderlyUser, $elderlyProfile, $caregiverUser] = $this->createLinkedPair();

        $alert = Alert::create([
            'elderly_id' => $elderlyProfile->id,
            'severity' => 'critical',
            'source_type' => 'vital_threshold',
            'title' => 'Critical BP',
            'message' => 'BP is 190/130',
            'state' => 'acknowledged',
        ]);

        $res = $this->actingAs($caregiverUser)
            ->postJson(route('caregiver.alerts.resolve', $alert));

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Alert resolved successfully.',
            ]);

        $this->assertEquals('resolved', $alert->fresh()->state);
        $this->assertNotNull($alert->fresh()->resolved_at);
        $this->assertEquals($caregiverUser->id, $alert->fresh()->resolved_by);
    }

    public function test_unauthorized_caregiver_cannot_acknowledge_other_patient_alert(): void
    {
        [$elderlyUser, $elderlyProfile] = $this->createLinkedPair();

        // Create another unrelated caregiver
        $otherCaregiver = User::factory()->create();
        UserProfile::create([
            'user_id' => $otherCaregiver->id,
            'user_type' => 'caregiver',
            'username' => 'othercaregiver',
            'profile_completed' => true,
        ]);

        $alert = Alert::create([
            'elderly_id' => $elderlyProfile->id,
            'severity' => 'emergency',
            'source_type' => 'sos',
            'title' => 'Emergency SOS',
            'message' => 'SOS triggered',
            'state' => 'open',
        ]);

        $res = $this->actingAs($otherCaregiver)
            ->postJson(route('caregiver.alerts.acknowledge', $alert));

        $res->assertStatus(403);
    }

    public function test_escalate_command_re_escalates_overdue_unacknowledged_alerts(): void
    {
        [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile] = $this->createLinkedPair();

        $pastTime = Carbon::now()->subMinutes(20);

        $alert = Alert::create([
            'elderly_id' => $elderlyProfile->id,
            'severity' => 'critical',
            'source_type' => 'vital_threshold',
            'title' => 'Overdue Alert',
            'message' => 'Overdue unacknowledged critical reading',
            'state' => 'open',
            'escalate_at' => $pastTime,
        ]);

        $this->artisan('alerts:escalate')
            ->assertSuccessful();

        // escalate_at should be updated to a future time
        $this->assertTrue($alert->fresh()->escalate_at->gt(Carbon::now()));
    }
}
