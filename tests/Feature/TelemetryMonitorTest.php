<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\GoogleFitToken;
use App\Models\SyncTelemetryLog;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\TelemetryMonitorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelemetryMonitorTest extends TestCase
{
    use RefreshDatabase;

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
            'profile_completed' => true,
        ]);

        return [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile];
    }

    public function test_record_sync_writes_telemetry_log(): void
    {
        [$elderlyUser, $elderlyProfile] = $this->createLinkedPair();
        $service = app(TelemetryMonitorService::class);

        $log = $service->recordSync($elderlyProfile, 'google_fit', 'success', 15, 350, null);

        $this->assertInstanceOf(SyncTelemetryLog::class, $log);
        $this->assertDatabaseHas('sync_telemetry_logs', [
            'elderly_id' => $elderlyProfile->id,
            'service_name' => 'google_fit',
            'status' => 'success',
            'records_synced' => 15,
        ]);
    }

    public function test_check_integration_health_alerts_caregiver_on_expired_token(): void
    {
        [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile] = $this->createLinkedPair();

        // Create expired Google Fit token
        GoogleFitToken::create([
            'user_id' => $elderlyUser->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'mock_refresh_token',
            'expires_at' => Carbon::now()->subDays(2),
        ]);

        $service = app(TelemetryMonitorService::class);
        $result = $service->checkIntegrationHealth();

        $this->assertEquals(1, $result['monitored_count']);
        $this->assertEquals(1, $result['alerts_triggered']);

        $this->assertDatabaseHas('alerts', [
            'elderly_id' => $elderlyProfile->id,
            'source_type' => 'integration_telemetry',
            'severity' => 'warning',
            'state' => 'open',
        ]);
    }

    public function test_artisan_telemetry_monitor_command(): void
    {
        $this->artisan('telemetry:monitor-integrations')
            ->expectsOutputToContain('Checking third-party health integration telemetry...')
            ->assertExitCode(0);
    }
}
