<?php

namespace Tests\Feature;

use App\Console\Commands\HealthCheck;
use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\CaptureSession;
use App\Models\HealthMetric;
use App\Models\PatientAlertThreshold;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AiAssistantService;
use App\Services\ClinicalRulesService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Wave 4 — intelligence, telemetry and retention.
 *
 * M1  the caregiver AI is given computed facts and forbidden from inventing
 * M2  the clinical summary endpoint and threshold editor exist and are scoped
 * M4  the health check reports scheduler, delivery and queue state
 * M5  capture-session images expire and are purged
 * M8  the hypotension gap, and patient-facing alert wording
 */
class Wave4IntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** @return array{0: User, 1: UserProfile, 2: User, 3: UserProfile} */
    private function createLinkedPair(): array
    {
        $caregiverUser = User::factory()->create(['name' => 'Dana Caregiver']);
        $caregiver = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'danawave4',
            'profile_completed' => true,
        ]);

        $elderlyUser = User::factory()->create(['name' => 'Rosa Senior']);
        $elderly = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'rosawave4',
            'timezone' => 'Asia/Manila',
            'caregiver_id' => $caregiver->id,
            'profile_completed' => true,
        ]);

        return [$elderlyUser, $elderly, $caregiverUser, $caregiver];
    }

    // ── M1 ───────────────────────────────────────────────────────────

    public function test_the_caregiver_prompt_carries_computed_facts_and_forbids_invention(): void
    {
        [, $elderly, $caregiverUser] = $this->createLinkedPair();

        HealthMetric::create([
            'elderly_id' => $elderly->id,
            'type' => 'blood_pressure',
            'value_text' => '128/82',
            'unit' => 'mmHg',
            'measured_at' => now()->subHours(3),
            'source' => 'manual',
        ]);

        $service = app(AiAssistantService::class);

        $method = new \ReflectionMethod($service, 'buildCaregiverSystemPrompt');
        $method->setAccessible(true);
        $prompt = $method->invoke($service, $caregiverUser, $elderly->id);

        // The deterministic block must be present…
        $this->assertStringContainsString('COMPUTED CLINICAL FACTS', $prompt);
        $this->assertStringContainsString('Risk score:', $prompt);
        $this->assertStringContainsString('Medication adherence (7 days)', $prompt);
        $this->assertStringContainsString('Data freshness', $prompt);

        // …and so must the constraint the plan specified.
        $this->assertStringContainsString('MUST NOT invent', $prompt);
        $this->assertStringContainsString('GROUNDING RULES', $prompt);
        $this->assertStringContainsString('Never state or imply a diagnosis', $prompt);
    }

    public function test_the_prompt_degrades_safely_without_a_profile(): void
    {
        [, , $caregiverUser] = $this->createLinkedPair();

        $service = app(AiAssistantService::class);
        $method = new \ReflectionMethod($service, 'buildClinicalFactsContext');
        $method->setAccessible(true);

        $text = $method->invoke($service, null);

        // It must not silently produce an empty facts block that the model
        // would then be free to fill in.
        $this->assertStringContainsString('No patient profile', $text);
    }

    // ── M2 ───────────────────────────────────────────────────────────

    public function test_caregiver_can_read_a_clinical_summary_with_freshness_and_provenance(): void
    {
        [, $elderly, $caregiverUser] = $this->createLinkedPair();

        HealthMetric::create([
            'elderly_id' => $elderly->id,
            'type' => 'heart_rate',
            'value' => 72,
            'unit' => 'bpm',
            'measured_at' => now()->subHour(),
            'source' => 'google_fit',
        ]);

        $response = $this->actingAs($caregiverUser)
            ->getJson(route('caregiver.patients.clinical-summary', $elderly))
            ->assertOk()
            ->assertJson(['success' => true]);

        $data = $response->json();

        $this->assertArrayHasKey('as_of', $data);
        $this->assertArrayHasKey('freshness', $data);
        $this->assertSame(1, $data['source_attribution']['google_fit'] ?? null);
        $this->assertArrayHasKey('disclaimer', $data);
    }

    public function test_clinical_summary_is_scoped_to_linked_patients(): void
    {
        [, $elderly] = $this->createLinkedPair();

        $otherUser = User::factory()->create();
        UserProfile::create([
            'user_id' => $otherUser->id,
            'user_type' => 'caregiver',
            'username' => 'unrelatedwave4',
            'profile_completed' => true,
        ]);

        $this->actingAs($otherUser)
            ->getJson(route('caregiver.patients.clinical-summary', $elderly))
            ->assertForbidden();
    }

    public function test_threshold_page_renders_a_form_for_the_caregiver(): void
    {
        [, $elderly, $caregiverUser] = $this->createLinkedPair();

        $this->actingAs($caregiverUser)
            ->get(route('caregiver.patients.thresholds.index', $elderly))
            ->assertOk()
            ->assertSee('Alert thresholds for', false)
            ->assertSee('thresholdEditor', false)
            ->assertSee('Reset to clinical default', false);
    }

    public function test_saved_thresholds_take_effect_on_the_next_reading(): void
    {
        [$elderlyUser, $elderly, $caregiverUser] = $this->createLinkedPair();

        $this->actingAs($caregiverUser)
            ->postJson(route('caregiver.patients.thresholds.update', $elderly), [
                'metric_type' => 'heart_rate',
                'thresholds' => [
                    'critical_high' => 95,
                    'critical_low' => 45,
                    'warning_high' => 85,
                    'warning_low' => 50,
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('patient_alert_thresholds', [
            'elderly_id' => $elderly->id,
            'metric_type' => 'heart_rate',
        ]);

        // 100 bpm is normal under the default (critical at 120) but critical
        // under this patient's doctor-set ceiling of 95.
        $metric = HealthMetric::create([
            'elderly_id' => $elderly->id,
            'type' => 'heart_rate',
            'value' => 100,
            'unit' => 'bpm',
            'measured_at' => now(),
            'source' => 'manual',
        ]);

        $alert = app(ClinicalRulesService::class)->evaluateVitalReading($metric);

        $this->assertNotNull($alert);
        $this->assertSame('critical', $alert->severity);
    }

    // ── M8: the hypotension gap ──────────────────────────────────────

    public function test_a_low_blood_pressure_reading_now_warns(): void
    {
        [, $elderly] = $this->createLinkedPair();

        $metric = HealthMetric::create([
            'elderly_id' => $elderly->id,
            'type' => 'blood_pressure',
            'value_text' => '95/58',
            'unit' => 'mmHg',
            'measured_at' => now(),
            'source' => 'manual',
        ]);

        $alert = app(ClinicalRulesService::class)->evaluateVitalReading($metric);

        $this->assertNotNull($alert, '95/58 is hypotensive and previously produced nothing.');
        $this->assertSame('warning', $alert->severity);
        $this->assertStringContainsString('Low Blood Pressure', $alert->title);
    }

    public function test_a_normal_reading_still_produces_no_alert(): void
    {
        [, $elderly] = $this->createLinkedPair();

        $metric = HealthMetric::create([
            'elderly_id' => $elderly->id,
            'type' => 'blood_pressure',
            'value_text' => '118/76',
            'unit' => 'mmHg',
            'measured_at' => now(),
            'source' => 'manual',
        ]);

        $this->assertNull(app(ClinicalRulesService::class)->evaluateVitalReading($metric));
    }

    // ── M8: patient-facing wording ───────────────────────────────────

    public function test_the_patient_and_caregiver_get_differently_worded_notifications(): void
    {
        [, $elderly, , $caregiver] = $this->createLinkedPair();

        $metric = HealthMetric::create([
            'elderly_id' => $elderly->id,
            'type' => 'blood_pressure',
            'value_text' => '190/130',
            'unit' => 'mmHg',
            'measured_at' => now(),
            'source' => 'manual',
        ]);

        app(ClinicalRulesService::class)->evaluateVitalReading($metric);

        $patientNotice = \App\Models\Notification::where('elderly_id', $elderly->id)->latest('id')->first();
        $caregiverNotice = \App\Models\Notification::where('elderly_id', $caregiver->id)->latest('id')->first();

        $this->assertNotNull($patientNotice);
        $this->assertNotNull($caregiverNotice);

        // The caregiver gets the clinical wording…
        $this->assertStringContainsString('Critical Blood Pressure', $caregiverNotice->title);

        // …the senior gets something calm and actionable instead of
        // "Immediate clinical review is recommended".
        $this->assertStringNotContainsString('Critical Blood Pressure', $patientNotice->title);
        $this->assertStringNotContainsString('clinical review', $patientNotice->message);
    }

    // ── M5 ───────────────────────────────────────────────────────────

    public function test_expired_capture_sessions_and_their_images_are_purged(): void
    {
        Storage::fake('public');
        [, $elderly] = $this->createLinkedPair();

        Storage::disk('public')->put('captures/old.jpg', 'image-bytes');
        Storage::disk('public')->put('captures/fresh.jpg', 'image-bytes');

        $expired = CaptureSession::create([
            'elderly_id' => $elderly->id,
            'session_type' => 'prescription_scan',
            'image_path' => 'captures/old.jpg',
            'status' => 'confirmed',
            'expires_at' => now()->subHour(),
        ]);

        $current = CaptureSession::create([
            'elderly_id' => $elderly->id,
            'session_type' => 'prescription_scan',
            'image_path' => 'captures/fresh.jpg',
            'status' => 'pending',
            'expires_at' => now()->addHours(12),
        ]);

        $this->artisan('captures:purge-expired')->assertSuccessful();

        $this->assertDatabaseMissing('capture_sessions', ['id' => $expired->id]);
        $this->assertDatabaseHas('capture_sessions', ['id' => $current->id]);

        // PHI must actually leave the disk, not just the database.
        Storage::disk('public')->assertMissing('captures/old.jpg');
        Storage::disk('public')->assertExists('captures/fresh.jpg');
    }

    public function test_dry_run_purges_nothing(): void
    {
        Storage::fake('public');
        [, $elderly] = $this->createLinkedPair();

        Storage::disk('public')->put('captures/old.jpg', 'bytes');
        $session = CaptureSession::create([
            'elderly_id' => $elderly->id,
            'session_type' => 'vital_photo_ocr',
            'image_path' => 'captures/old.jpg',
            'status' => 'processed',
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('captures:purge-expired --dry-run')->assertSuccessful();

        $this->assertDatabaseHas('capture_sessions', ['id' => $session->id]);
        Storage::disk('public')->assertExists('captures/old.jpg');
    }

    // ── M4 ───────────────────────────────────────────────────────────

    public function test_health_check_fails_when_the_scheduler_has_not_run(): void
    {
        Cache::forget(HealthCheck::HEARTBEAT_KEY);

        $this->artisan('silvercare:health-check')
            ->expectsOutputToContain('Scheduler has never run')
            ->assertFailed();
    }

    public function test_health_check_passes_with_a_recent_heartbeat_and_clean_state(): void
    {
        Cache::put(HealthCheck::HEARTBEAT_KEY, now()->toISOString(), now()->addDay());

        $this->artisan('silvercare:health-check')
            ->expectsOutputToContain('All checks healthy')
            ->assertSuccessful();
    }

    public function test_health_check_reports_failed_alert_deliveries(): void
    {
        Cache::put(HealthCheck::HEARTBEAT_KEY, now()->toISOString(), now()->addDay());
        [, $elderly, , $caregiver] = $this->createLinkedPair();

        $alert = Alert::create([
            'elderly_id' => $elderly->id,
            'severity' => 'critical',
            'source_type' => 'vital_threshold',
            'title' => 'Critical reading',
            'message' => 'A critical reading was recorded.',
            'state' => 'open',
        ]);

        AlertDelivery::create([
            'alert_id' => $alert->id,
            'recipient_profile_id' => $caregiver->id,
            'channel' => 'in_app',
            'state' => 'failed',
            'error' => 'simulated failure',
        ]);

        $this->artisan('silvercare:health-check')
            ->expectsOutputToContain('alert delivery failure')
            ->assertFailed();
    }

    public function test_health_check_emits_json_when_asked(): void
    {
        Cache::put(HealthCheck::HEARTBEAT_KEY, now()->toISOString(), now()->addDay());

        $this->artisan('silvercare:health-check --json')->assertSuccessful();
    }
}
