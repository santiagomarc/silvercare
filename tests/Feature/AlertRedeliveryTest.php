<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AlertDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Recovery path for deliveries lost to C1.
 *
 * While notifications_severity_check was stale, every in-app delivery of a
 * critical or emergency alert failed and was swallowed into an
 * alert_deliveries row. `alerts:redeliver-failed` walks those rows and retries
 * the channels that failed.
 */
class AlertRedeliveryTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: UserProfile, 1: UserProfile} */
    private function createLinkedPair(): array
    {
        $caregiverUser = User::factory()->create(['name' => 'Dana Caregiver']);
        $caregiver = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'danacare2',
            'profile_completed' => true,
        ]);

        $elderlyUser = User::factory()->create(['name' => 'Rosa Senior']);
        $elderly = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'rosasenior2',
            'caregiver_id' => $caregiver->id,
            'profile_completed' => true,
        ]);

        return [$elderly, $caregiver];
    }

    private function createAlertWithFailedDelivery(UserProfile $elderly, UserProfile $caregiver): Alert
    {
        $alert = Alert::create([
            'elderly_id' => $elderly->id,
            'severity' => 'critical',
            'source_type' => 'vital_threshold',
            'title' => '🚨 Critical Blood Pressure (185/125 mmHg)',
            'message' => 'A critical blood pressure reading was recorded.',
            'metadata' => ['metric_type' => 'blood_pressure'],
            'state' => 'open',
            'escalate_at' => now()->addMinutes(15),
        ]);

        // Exactly what C1 left behind: the write was rejected and swallowed.
        AlertDelivery::create([
            'alert_id' => $alert->id,
            'recipient_profile_id' => $caregiver->id,
            'channel' => 'in_app',
            'state' => 'failed',
            'error' => 'SQLSTATE[23514]: Check violation: notifications_severity_check',
        ]);

        return $alert;
    }

    public function test_redelivery_recovers_a_failed_in_app_delivery(): void
    {
        Mail::fake();
        [$elderly, $caregiver] = $this->createLinkedPair();
        $alert = $this->createAlertWithFailedDelivery($elderly, $caregiver);

        $this->assertDatabaseMissing('notifications', ['elderly_id' => $caregiver->id]);

        $result = app(AlertDeliveryService::class)->retryFailedDeliveries($alert);

        $this->assertSame(1, $result['retried']);
        $this->assertSame(1, $result['recovered']);
        $this->assertSame(0, $result['skipped']);

        // The caregiver now has the notification they never received.
        $this->assertDatabaseHas('notifications', [
            'elderly_id' => $caregiver->id,
            'severity' => 'critical',
        ]);

        // The original failure is preserved — "failed at T1, delivered at T2".
        $this->assertDatabaseHas('alert_deliveries', [
            'alert_id' => $alert->id,
            'channel' => 'in_app',
            'state' => 'failed',
        ]);
        $this->assertDatabaseHas('alert_deliveries', [
            'alert_id' => $alert->id,
            'channel' => 'in_app',
            'state' => 'delivered',
        ]);
    }

    public function test_redelivery_is_safe_to_rerun(): void
    {
        Mail::fake();
        [$elderly, $caregiver] = $this->createLinkedPair();
        $alert = $this->createAlertWithFailedDelivery($elderly, $caregiver);

        $service = app(AlertDeliveryService::class);
        $service->retryFailedDeliveries($alert);

        $secondPass = $service->retryFailedDeliveries($alert);

        $this->assertSame(0, $secondPass['retried'], 'A recovered channel must not be retried again.');
        $this->assertSame(1, $secondPass['skipped']);

        $this->assertSame(
            1,
            AlertDelivery::where('alert_id', $alert->id)
                ->where('channel', 'in_app')
                ->where('state', 'delivered')
                ->count(),
            'Re-running must not send the caregiver a duplicate.'
        );
    }

    public function test_command_reports_and_skips_resolved_alerts_by_default(): void
    {
        Mail::fake();
        [$elderly, $caregiver] = $this->createLinkedPair();
        $alert = $this->createAlertWithFailedDelivery($elderly, $caregiver);
        $alert->update(['state' => 'resolved', 'resolved_at' => now()]);

        $this->artisan('alerts:redeliver-failed')
            ->expectsOutputToContain('No alerts with failed deliveries')
            ->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['elderly_id' => $caregiver->id]);

        // …but --include-resolved picks it up.
        $this->artisan('alerts:redeliver-failed --include-resolved')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'elderly_id' => $caregiver->id,
            'severity' => 'critical',
        ]);
    }

    public function test_dry_run_sends_nothing(): void
    {
        Mail::fake();
        [$elderly, $caregiver] = $this->createLinkedPair();
        $this->createAlertWithFailedDelivery($elderly, $caregiver);

        $this->artisan('alerts:redeliver-failed --dry-run')
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['elderly_id' => $caregiver->id]);
        $this->assertDatabaseMissing('alert_deliveries', [
            'channel' => 'in_app',
            'state' => 'delivered',
        ]);
    }
}
