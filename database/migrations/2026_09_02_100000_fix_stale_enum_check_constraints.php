<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * C1 — Repair the stale CHECK constraints left behind by earlier `->change()` calls.
 *
 * On PostgreSQL, `$table->enum(...)` is rendered as a VARCHAR plus a CHECK
 * constraint. Widening the column later with `$table->string(...)->change()`
 * alters the column type but leaves that CHECK in place, so the new values are
 * still rejected at write time. SQLite emits no such constraint, which is why
 * the test suite never noticed.
 *
 * Three constraints were stale, each silently breaking a shipped feature:
 *
 *   notifications_severity_check   — rejected 'critical', 'emergency' and
 *                                    'urgent', so every critical/emergency
 *                                    in-app alert delivery failed and was
 *                                    swallowed into alert_deliveries.state =
 *                                    'failed'. Also broke medication refill
 *                                    requests, which write 'urgent'.
 *
 *   health_metrics_source_check    — rejected 'voice_capture', 'camera_ocr',
 *                                    'offline_sync' and 'system', breaking
 *                                    voice vital capture, prescription/vital
 *                                    OCR, offline vital sync, and the nightly
 *                                    ai:track-cognitive-sentiment command.
 *
 *   health_metrics_type_check      — rejected 'ai_mood_score' and
 *                                    'ai_confusion_index', which
 *                                    TrackCognitiveSentiment has always
 *                                    written.
 *
 * Each constraint is rebuilt covering every value the application actually
 * writes today. SchemaConstraintTest asserts that set stays in sync, so the
 * next value added to the code fails in CI rather than in production.
 */
return new class extends Migration
{
    /**
     * Constraint name => [column, allowed values].
     */
    private const CONSTRAINTS = [
        'notifications' => [
            'notifications_severity_check' => [
                'column' => 'severity',
                'values' => [
                    // Original enum
                    'positive', 'negative', 'reminder', 'warning',
                    // Clinical alert center (sprint 2)
                    'critical', 'emergency',
                    // Medication refill requests
                    'urgent',
                ],
            ],
        ],
        'health_metrics' => [
            'health_metrics_source_check' => [
                'column' => 'source',
                'values' => [
                    // Original enum
                    'manual', 'google_fit', 'device',
                    // Multimodal capture (sprint 4)
                    'voice_capture', 'camera_ocr',
                    // Offline reconciliation (sprint 5)
                    'offline_sync',
                    // Scheduled/derived metrics (TrackCognitiveSentiment)
                    'system',
                ],
            ],
            'health_metrics_type_check' => [
                'column' => 'type',
                'values' => [
                    // Original enum
                    'blood_pressure', 'heart_rate', 'sugar_level', 'temperature',
                    'mood', 'steps', 'calories', 'sleep', 'weight',
                    // Derived cognitive metrics (TrackCognitiveSentiment)
                    'ai_mood_score', 'ai_confusion_index',
                ],
            ],
        ],
    ];

    public function up(): void
    {
        // CHECK constraints of this shape only exist on PostgreSQL. On SQLite
        // (local/CI fallback) there is nothing to repair.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::CONSTRAINTS as $table => $constraints) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($constraints as $name => $spec) {
                $this->dropConstraint($table, $name);
                $this->addValueConstraint($table, $name, $spec['column'], $spec['values']);
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Restore the original, narrower enum value sets. Rows written since
        // this migration ran may violate them, so drop any offending row's
        // constraint rather than failing the rollback silently — we re-add the
        // constraint as NOT VALID so existing data is left untouched and only
        // new writes are checked.
        $original = [
            'notifications' => [
                'notifications_severity_check' => [
                    'column' => 'severity',
                    'values' => ['positive', 'negative', 'reminder', 'warning'],
                ],
            ],
            'health_metrics' => [
                'health_metrics_source_check' => [
                    'column' => 'source',
                    'values' => ['manual', 'google_fit', 'device'],
                ],
                'health_metrics_type_check' => [
                    'column' => 'type',
                    'values' => [
                        'blood_pressure', 'heart_rate', 'sugar_level', 'temperature',
                        'mood', 'steps', 'calories', 'sleep', 'weight',
                    ],
                ],
            ],
        ];

        foreach ($original as $table => $constraints) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($constraints as $name => $spec) {
                $this->dropConstraint($table, $name);
                $this->addValueConstraint($table, $name, $spec['column'], $spec['values'], notValid: true);
            }
        }
    }

    private function dropConstraint(string $table, string $name): void
    {
        DB::statement(sprintf(
            'ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s',
            $this->quote($table),
            $this->quote($name)
        ));
    }

    /**
     * @param  list<string>  $values
     */
    private function addValueConstraint(
        string $table,
        string $name,
        string $column,
        array $values,
        bool $notValid = false
    ): void {
        $list = implode(', ', array_map(
            fn (string $v): string => "'" . str_replace("'", "''", $v) . "'",
            $values
        ));

        DB::statement(sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s CHECK (%s::text = ANY (ARRAY[%s]::text[]))%s',
            $this->quote($table),
            $this->quote($name),
            $this->quote($column),
            $list,
            $notValid ? ' NOT VALID' : ''
        ));
    }

    private function quote(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
};
