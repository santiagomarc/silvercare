<?php

namespace App\Services;

use App\Models\DoseInstance;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DoseAdministrationService
{
    public const DEFAULT_GRACE_MINUTES = 60;

    public function __construct(
        protected NotificationService $notificationService,
        protected DoseInstanceGeneratorService $generatorService,
    ) {
    }

    /**
     * Build dose window parameters (consolidated from MedicationWindowService).
     *
     * @return array{scheduled_time: Carbon, window_start: Carbon, window_end: Carbon, is_within_window: bool, is_past_window: bool, is_before_window: bool, can_take: bool, can_undo: bool}
     */
    public function evaluateWindow(Carbon $scheduledDateTime, ?Carbon $now = null, int $graceMinutes = self::DEFAULT_GRACE_MINUTES): array
    {
        $current = $now ? $now->copy() : Carbon::now();
        $windowStart = $scheduledDateTime->copy();
        $windowEnd = $scheduledDateTime->copy()->addMinutes($graceMinutes);

        $isWithinWindow = $current->between($windowStart, $windowEnd);
        $isPastWindow = $current->gt($windowEnd);
        $isBeforeWindow = $current->lt($windowStart);

        return [
            'scheduled_time' => $scheduledDateTime,
            'window_start' => $windowStart,
            'window_end' => $windowEnd,
            'is_within_window' => $isWithinWindow,
            'is_past_window' => $isPastWindow,
            'is_before_window' => $isBeforeWindow,
            'can_take' => $isWithinWindow || $isPastWindow,
            'can_undo' => !$isPastWindow,
        ];
    }

    /**
     * Confirm a dose instance as taken.
     * Single auditable writer for all paths (senior UI, AI, offline sync, caregiver).
     */
    public function confirmDose(
        DoseInstance $instance,
        string $source = 'senior_ui',
        ?int $actorId = null,
        ?string $idempotencyKey = null,
        ?Carbon $now = null
    ): array {
        $now = $now ? $now->copy() : Carbon::now();

        // 1. Check idempotency key if provided
        if (!empty($idempotencyKey)) {
            $existingIdempotent = DoseInstance::where('idempotency_key', $idempotencyKey)->first();
            if ($existingIdempotent && $existingIdempotent->id === $instance->id && $existingIdempotent->isTaken()) {
                return [
                    'success' => true,
                    'is_taken' => true,
                    'taken_at' => $existingIdempotent->taken_at?->toISOString(),
                    'taken_late' => ($existingIdempotent->state === 'taken_late'),
                    'status' => $existingIdempotent->state,
                    'message' => 'Medication already marked as taken.',
                    'instance' => $existingIdempotent,
                ];
            }
        }

        return DB::transaction(function () use ($instance, $source, $actorId, $idempotencyKey, $now) {
            /** @var DoseInstance $lockedInstance */
            $lockedInstance = DoseInstance::where('id', $instance->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotent check on locked row
            if ($lockedInstance->isTaken()) {
                return [
                    'success' => true,
                    'is_taken' => true,
                    'taken_at' => $lockedInstance->taken_at?->toISOString(),
                    'taken_late' => ($lockedInstance->state === 'taken_late'),
                    'status' => $lockedInstance->state,
                    'message' => 'Medication already marked as taken.',
                    'instance' => $lockedInstance,
                ];
            }

            $medication = $lockedInstance->medication;
            if (!$medication || !$medication->is_active) {
                return [
                    'success' => false,
                    'error_code' => 'MEDICATION_INACTIVE',
                    'message' => 'This medication is not currently active.',
                ];
            }

            // Check date validity
            $today = $now->copy()->startOfDay();
            if ($medication->start_date && $today->lt($medication->start_date->copy()->startOfDay())) {
                return [
                    'success' => false,
                    'error_code' => 'MEDICATION_NOT_STARTED',
                    'message' => 'This medication schedule has not started yet.',
                ];
            }

            if ($medication->end_date && $today->gt($medication->end_date->copy()->endOfDay())) {
                return [
                    'success' => false,
                    'error_code' => 'MEDICATION_EXPIRED',
                    'message' => 'This medication prescription has expired.',
                ];
            }

            // Window evaluation
            $timezone = $lockedInstance->timezone ?: config('app.timezone', 'Asia/Manila');
            $localScheduled = $lockedInstance->scheduled_at_utc->copy()->setTimezone($timezone);
            $localNow = $now->copy()->setTimezone($timezone);

            $window = $this->evaluateWindow($localScheduled, $localNow);

            if ($window['is_before_window']) {
                return [
                    'success' => false,
                    'error_code' => 'DOSE_TOO_EARLY',
                    'message' => 'Too early to take this medication. Please wait until ' . $window['window_start']->format('g:i A'),
                    'can_take' => false,
                    'window_start' => $window['window_start']->toISOString(),
                ];
            }

            $state = $window['is_past_window'] ? 'taken_late' : 'taken';
            $takenLate = ($state === 'taken_late');

            // Update DoseInstance
            $lockedInstance->update([
                'state' => $state,
                'taken_at' => $now,
                'source' => $source,
                'actor_id' => $actorId,
                'idempotency_key' => $idempotencyKey,
                'version' => $lockedInstance->version + 1,
            ]);

            // Sync to legacy MedicationLog for backward compatibility
            MedicationLog::updateOrCreate(
                [
                    'elderly_id' => $lockedInstance->elderly_id,
                    'medication_id' => $medication->id,
                    'scheduled_time' => $localScheduled,
                ],
                [
                    'is_taken' => true,
                    'taken_at' => $now,
                ]
            );

            // Atomically decrement stock if tracked
            if ($medication->track_inventory && $medication->current_stock > 0) {
                $medication->decrement('current_stock');
            }

            // Create notification
            $this->notificationService->createMedicationTakenNotification(
                $lockedInstance->elderly_id,
                $medication->name,
                $takenLate
            );

            return [
                'success' => true,
                'is_taken' => true,
                'taken_at' => $now->toISOString(),
                'taken_late' => $takenLate,
                'status' => $state,
                'message' => $takenLate ? 'Medication marked as taken (late)' : 'Medication taken!',
                'instance' => $lockedInstance,
            ];
        });
    }

    /**
     * Confirm a dose by medication model and time string (e.g. "08:00").
     */
    public function confirmDoseByMedicationAndTime(
        Medication $medication,
        int $elderlyId,
        string $timeStr,
        string $source = 'senior_ui',
        ?int $actorId = null,
        ?string $idempotencyKey = null,
        ?Carbon $now = null
    ): array {
        $now = $now ? $now->copy() : Carbon::now();
        $elderly = $medication->elderly ?? UserProfile::find($elderlyId);
        $timezone = $elderly?->timezone ?: config('app.timezone', 'Asia/Manila');

        $localDate = $now->copy()->setTimezone($timezone)->startOfDay();
        $scheduledLocal = Carbon::parse($localDate->format('Y-m-d') . ' ' . $timeStr, $timezone);
        $scheduledUtc = $scheduledLocal->copy()->setTimezone('UTC');

        // Verify scheduled for today
        $validTimes = $medication->scheduleTimesForDate($localDate);
        if (!in_array($scheduledLocal->format('H:i'), $validTimes, true) && !in_array($timeStr, $validTimes, true)) {
            return [
                'success' => false,
                'error_code' => 'UNSCHEDULED_DOSE',
                'message' => "Medication is not scheduled for {$timeStr} today.",
            ];
        }

        // Find or create DoseInstance
        $instance = DoseInstance::firstOrCreate(
            [
                'elderly_id' => $elderlyId,
                'medication_id' => $medication->id,
                'scheduled_at_utc' => $scheduledUtc,
            ],
            [
                'local_date' => $localDate->format('Y-m-d'),
                'timezone' => $timezone,
                'state' => 'pending',
                'version' => 1,
            ]
        );

        return $this->confirmDose($instance, $source, $actorId, $idempotencyKey, $now);
    }

    /**
     * Undo a taken medication dose.
     */
    public function undoDose(
        DoseInstance $instance,
        string $source = 'senior_ui',
        ?int $actorId = null,
        ?Carbon $now = null
    ): array {
        $now = $now ? $now->copy() : Carbon::now();

        return DB::transaction(function () use ($instance, $source, $actorId, $now) {
            /** @var DoseInstance $lockedInstance */
            $lockedInstance = DoseInstance::where('id', $instance->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedInstance->isTaken()) {
                return [
                    'success' => true,
                    'is_taken' => false,
                    'message' => 'Medication was not marked as taken.',
                    'instance' => $lockedInstance,
                ];
            }

            $timezone = $lockedInstance->timezone ?: config('app.timezone', 'Asia/Manila');
            $localScheduled = $lockedInstance->scheduled_at_utc->copy()->setTimezone($timezone);
            $localNow = $now->copy()->setTimezone($timezone);

            $window = $this->evaluateWindow($localScheduled, $localNow);

            if (!$window['can_undo']) {
                return [
                    'success' => false,
                    'error_code' => 'UNDO_WINDOW_EXPIRED',
                    'message' => 'Cannot unmark - grace period has ended',
                ];
            }

            $medication = $lockedInstance->medication;

            $lockedInstance->update([
                'state' => 'pending',
                'taken_at' => null,
                'source' => $source,
                'actor_id' => $actorId,
                'version' => $lockedInstance->version + 1,
            ]);

            // Sync legacy MedicationLog - delete so queries expecting no taken log pass
            $log = MedicationLog::where('elderly_id', $lockedInstance->elderly_id)
                ->where('medication_id', $lockedInstance->medication_id)
                ->where('scheduled_time', $localScheduled)
                ->first();

            if ($log) {
                $log->delete();
            }

            // Restore inventory
            if ($medication && $medication->track_inventory) {
                $medication->increment('current_stock');
            }

            return [
                'success' => true,
                'is_taken' => false,
                'message' => 'Medication unmarked',
                'instance' => $lockedInstance,
            ];
        });
    }

    /**
     * Undo a dose by medication model and time string.
     */
    public function undoDoseByMedicationAndTime(
        Medication $medication,
        int $elderlyId,
        string $timeStr,
        string $source = 'senior_ui',
        ?int $actorId = null,
        ?Carbon $now = null
    ): array {
        $now = $now ? $now->copy() : Carbon::now();
        $elderly = $medication->elderly ?? UserProfile::find($elderlyId);
        $timezone = $elderly?->timezone ?: config('app.timezone', 'Asia/Manila');

        $localDate = $now->copy()->setTimezone($timezone)->startOfDay();
        $scheduledLocal = Carbon::parse($localDate->format('Y-m-d') . ' ' . $timeStr, $timezone);
        $scheduledUtc = $scheduledLocal->copy()->setTimezone('UTC');

        $instance = DoseInstance::where('elderly_id', $elderlyId)
            ->where('medication_id', $medication->id)
            ->where('scheduled_at_utc', $scheduledUtc)
            ->first();

        if (!$instance) {
            // Check legacy log
            $log = MedicationLog::where('elderly_id', $elderlyId)
                ->where('medication_id', $medication->id)
                ->where('scheduled_time', $scheduledLocal)
                ->first();

            if (!$log || !$log->is_taken) {
                return [
                    'success' => true,
                    'is_taken' => false,
                    'message' => 'Medication is not marked as taken.',
                ];
            }

            // Create instance to manage it
            $instance = DoseInstance::create([
                'elderly_id' => $elderlyId,
                'medication_id' => $medication->id,
                'scheduled_at_utc' => $scheduledUtc,
                'local_date' => $localDate->format('Y-m-d'),
                'timezone' => $timezone,
                'state' => 'taken',
                'taken_at' => $log->taken_at ?: $now,
                'version' => 1,
            ]);
        }

        return $this->undoDose($instance, $source, $actorId, $now);
    }

    /**
     * Mark a dose as missed (called by scheduler or rules engine).
     */
    public function markMissed(DoseInstance $instance): void
    {
        if ($instance->isPending()) {
            $instance->update([
                'state' => 'missed',
                'version' => $instance->version + 1,
            ]);

            $medication = $instance->medication;
            if ($medication) {
                $timezone = $instance->timezone ?: config('app.timezone', 'Asia/Manila');
                $localTime = $instance->scheduled_at_utc->copy()->setTimezone($timezone);

                $this->notificationService->createMedicationMissedNotification(
                    $instance->elderly_id,
                    $medication->name,
                    $localTime->format('g:i A')
                );
            }
        }
    }
}
