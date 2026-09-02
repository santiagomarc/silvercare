<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * C1 regression guard.
 *
 * Laravel renders `$table->enum(...)` on PostgreSQL as a VARCHAR plus a CHECK
 * constraint, and a later `$table->string(...)->change()` does NOT drop that
 * CHECK. Three shipped features were writing values the database still
 * rejected, and the failures were swallowed by try/catch blocks in production.
 *
 * These tests write every value the application actually uses straight at the
 * database. When someone introduces a new severity, source or metric type
 * without widening the constraint, the test fails here instead of the feature
 * failing silently for a caregiver.
 *
 * Keep the arrays below in sync with the constraint definitions in
 * 2026_09_02_100000_fix_stale_enum_check_constraints.php.
 */
class SchemaConstraintTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Severities written anywhere in app/. Sources, in order of introduction:
     * the original enum, the sprint 2 clinical alert center
     * (AlertDeliveryService::deliverInApp), and medication refill requests
     * (ElderlyDashboardController::requestRefill).
     */
    public const NOTIFICATION_SEVERITIES = [
        'positive', 'negative', 'reminder', 'warning',
        'critical', 'emergency', 'urgent',
    ];

    /**
     * Sources written to health_metrics: the original enum, sprint 4
     * multimodal capture, sprint 5 offline reconciliation, and the nightly
     * TrackCognitiveSentiment command.
     */
    public const HEALTH_METRIC_SOURCES = [
        'manual', 'google_fit', 'device',
        'voice_capture', 'camera_ocr',
        'offline_sync',
        'system',
    ];

    /**
     * Metric types written to health_metrics, including the derived cognitive
     * metrics produced by TrackCognitiveSentiment.
     */
    public const HEALTH_METRIC_TYPES = [
        'blood_pressure', 'heart_rate', 'sugar_level', 'temperature',
        'mood', 'steps', 'calories', 'sleep', 'weight',
        'ai_mood_score', 'ai_confusion_index',
    ];

    private function elderlyProfileId(): int
    {
        $user = User::factory()->create();

        return UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'elderly',
            'username' => 'schema_probe_' . $user->id,
            'profile_completed' => true,
        ])->id;
    }

    public function test_every_notification_severity_the_app_writes_is_accepted(): void
    {
        $elderlyId = $this->elderlyProfileId();

        foreach (self::NOTIFICATION_SEVERITIES as $severity) {
            try {
                DB::table('notifications')->insert([
                    'elderly_id' => $elderlyId,
                    'type' => 'schema_probe',
                    'title' => 'Schema probe',
                    'message' => 'Schema probe',
                    'severity' => $severity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                $this->fail(
                    "notifications.severity rejected '{$severity}', which the application writes. "
                    . "Widen notifications_severity_check. Driver error: {$e->getMessage()}"
                );
            }
        }

        $this->assertDatabaseCount('notifications', count(self::NOTIFICATION_SEVERITIES));
    }

    public function test_every_health_metric_source_the_app_writes_is_accepted(): void
    {
        $elderlyId = $this->elderlyProfileId();

        foreach (self::HEALTH_METRIC_SOURCES as $source) {
            try {
                DB::table('health_metrics')->insert([
                    'elderly_id' => $elderlyId,
                    'type' => 'heart_rate',
                    'value' => 72,
                    'unit' => 'bpm',
                    'measured_at' => now(),
                    'source' => $source,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                $this->fail(
                    "health_metrics.source rejected '{$source}', which the application writes. "
                    . "Widen health_metrics_source_check. Driver error: {$e->getMessage()}"
                );
            }
        }

        $this->assertDatabaseCount('health_metrics', count(self::HEALTH_METRIC_SOURCES));
    }

    public function test_every_health_metric_type_the_app_writes_is_accepted(): void
    {
        $elderlyId = $this->elderlyProfileId();

        foreach (self::HEALTH_METRIC_TYPES as $type) {
            try {
                DB::table('health_metrics')->insert([
                    'elderly_id' => $elderlyId,
                    'type' => $type,
                    'value' => 1,
                    'unit' => 'unit',
                    'measured_at' => now(),
                    'source' => 'manual',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                $this->fail(
                    "health_metrics.type rejected '{$type}', which the application writes. "
                    . "Widen health_metrics_type_check. Driver error: {$e->getMessage()}"
                );
            }
        }

        $this->assertDatabaseCount('health_metrics', count(self::HEALTH_METRIC_TYPES));
    }

    /**
     * The repair migration must not have removed the constraints altogether —
     * they are still the backstop against typos reaching the alert pipeline.
     */
    public function test_constraints_still_reject_unknown_values(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('CHECK constraints of this shape only exist on PostgreSQL.');
        }

        $elderlyId = $this->elderlyProfileId();

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('notifications')->insert([
            'elderly_id' => $elderlyId,
            'type' => 'schema_probe',
            'title' => 'Schema probe',
            'message' => 'Schema probe',
            'severity' => 'definitely_not_a_real_severity',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
