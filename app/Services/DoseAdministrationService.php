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
    public const DEFAULT_MAX_LATE_MINUTES = 360;

    public function __construct(
        protected NotificationService $notificationService,
        protected DoseInstanceGeneratorService $generatorService,
        protected ClinicalRulesService $rulesService,
    ) {
    }

    /**
     * Build dose window parameters. Sole owner of this rule — MedicationWindowService
     * was deleted in favour of this method so the logic cannot drift in two places.
     *
     * C5: `can_take` is bounded. The previous expression was
     * `$isWithinWindow || $isPastWindow`, which stayed true forever, so an 08:00
     * dose could be confirmed at 23:00 and recorded as merely "late". Past
     * max_late_minutes the dose is expired: it can no longer be truthfully
     * confirmed and must be skipped with a reason instead.
     *
     * @return array{scheduled_time: Carbon, window_start: Carbon, window_end: Carbon, late_deadline: Carbon, is_within_window: bool, is_past_window: bool, is_before_window: bool, is_expired: bool, can_take: bool, can_undo: bool}
     */
    public function evaluateWindow(
        Carbon $scheduledDateTime,
        ?Carbon $now = null,
        ?int $graceMinutes = null,
        ?int $maxLateMinutes = null
    ): array {
        $graceMinutes ??= (int) config('medications.grace_minutes', self::DEFAULT_GRACE_MINUTES);
        $maxLateMinutes ??= (int) config('medications.max_late_minutes', self::DEFAULT_MAX_LATE_MINUTES);

        // A max-late shorter than the grace window would make doses expire
        // before they stop being on time. Treat grace as the floor.
        $maxLateMinutes = max($maxLateMinutes, $graceMinutes);

        $current = $now ? $now->copy() : Carbon::now();
        $windowStart = $scheduledDateTime->copy();
        $windowEnd = $scheduledDateTime->copy()->addMinutes($graceMinutes);
        $lateDeadline = $scheduledDateTime->copy()->addMinutes($maxLateMinutes);

        $isWithinWindow = $current->between($windowStart, $windowEnd);
        $isPastWindow = $current->gt($windowEnd);
        $isBeforeWindow = $current->lt($windowStart);
        $isExpired = $current->gt($lateDeadline);

        return [
            'scheduled_time' => $scheduledDateTime,
            'window_start' => $windowStart,
            'window_end' => $windowEnd,
            'late_deadline' => $lateDeadline,
            'is_within_window' => $isWithinWindow,
            'is_past_window' => $isPastWindow,
            'is_before_window' => $isBeforeWindow,
            'is_expired' => $isExpired,
            'can_take' => ($isWithinWindow || $isPastWindow) && ! $isExpired,
            'can_undo' => ! $isPastWindow,
        ];
    }

    /**
     * Evaluate the window for a "HH:MM" time on today's local date.
     *
     * Replaces MedicationWindowService::forToday() for the presenter and
     * controller call sites.
     *
     * @return array{scheduled_time: Carbon, window_start: Carbon, window_end: Carbon, late_deadline: Carbon, is_within_window: bool, is_past_window: bool, is_before_window: bool, is_expired: bool, can_take: bool, can_undo: bool}
     */
    public function evaluateWindowForTime(string $timeStr, ?Carbon $now = null, ?string $timezone = null): array
    {
        $tz = $timezone ?: config('app.timezone', 'Asia/Manila');
        $current = $now ? $now->copy() : Carbon::now($tz);
        $scheduled = Carbon::parse(Carbon::now($tz)->format('Y-m-d') . ' ' . $timeStr, $tz);

        return $this->evaluateWindow($scheduled, $current);
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

        // 1. Idempotency. A replayed key for this same dose is a duplicate
        //    submission and returns the original outcome. A key already spent
        //    on a *different* dose is a client bug or a collision: previously
        //    this fell through to an UPDATE that violated the unique index and
        //    surfaced as a 500 (H2). Now it is an explicit conflict.
        if (!empty($idempotencyKey)) {
            $existingIdempotent = DoseInstance::where('elderly_id', $instance->elderly_id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingIdempotent && $existingIdempotent->id !== $instance->id) {
                return [
                    'success' => false,
                    'error_code' => 'IDEMPOTENCY_KEY_REUSED',
                    'message' => 'This request key was already used for a different dose.',
                    'conflicting_dose_instance_id' => $existingIdempotent->id,
                ];
            }

            if ($existingIdempotent && $existingIdempotent->isTaken()) {
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

            // A dose the caregiver deliberately held or the senior skipped is
            // not pending — confirming it would silently overwrite that decision.
            if (in_array($lockedInstance->state, ['held', 'skipped'], true)) {
                return [
                    'success' => false,
                    'error_code' => $lockedInstance->state === 'held' ? 'DOSE_HELD' : 'DOSE_SKIPPED',
                    'message' => $lockedInstance->state === 'held'
                        ? 'This dose was put on hold by your caregiver.'
                        : 'This dose was already marked as skipped.',
                    'state_reason' => $lockedInstance->state_reason,
                ];
            }

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

            // C5: past the outer bound this is no longer a truthful record of
            // taking the dose near its scheduled time. Refuse, and point the
            // senior at the honest alternative.
            if ($window['is_expired']) {
                return [
                    'success' => false,
                    'error_code' => 'DOSE_WINDOW_EXPIRED',
                    'message' => 'This dose is too far past its scheduled time to mark as taken. '
                        . 'Please skip it and tell your caregiver.',
                    'can_take' => false,
                    'scheduled_time' => $window['scheduled_time']->toISOString(),
                    'late_deadline' => $window['late_deadline']->toISOString(),
                ];
            }

            $state = $window['is_past_window'] ? 'taken_late' : 'taken';
            $takenLate = ($state === 'taken_late');

            // H1: decrement first so we know what actually came off stock, and
            // record it on the instance. Undo returns exactly this amount, so
            // confirming at zero stock and undoing can no longer mint a pill.
            $inventoryDelta = 0;

            if ($medication->track_inventory) {
                // Conditional UPDATE rather than read-then-decrement: two
                // concurrent confirms of different doses of the same medication
                // can no longer drive stock negative.
                $decremented = Medication::where('id', $medication->id)
                    ->where('current_stock', '>', 0)
                    ->update(['current_stock' => DB::raw('current_stock - 1')]);

                $inventoryDelta = $decremented > 0 ? 1 : 0;
            }

            // Update DoseInstance
            $lockedInstance->update([
                'state' => $state,
                'taken_at' => $now,
                'source' => $source,
                'actor_id' => $actorId,
                'idempotency_key' => $idempotencyKey,
                'inventory_delta' => $inventoryDelta,
                'state_reason' => null,
                'state_changed_at' => $now,
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

            // Create notification
            $this->notificationService->createMedicationTakenNotification(
                $lockedInstance->elderly_id,
                $medication->name,
                $takenLate
            );

            // Realtime Reverb Broadcast to linked caregiver
            if ($lockedInstance->elderly?->caregiver_id) {
                event(new \App\Events\DoseConfirmedEvent($lockedInstance, $lockedInstance->elderly->caregiver_id));
            }

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
            $inventoryDelta = (int) ($lockedInstance->inventory_delta ?? 0);

            $lockedInstance->update([
                'state' => 'pending',
                'taken_at' => null,
                'source' => $source,
                'actor_id' => $actorId,
                'inventory_delta' => 0,
                'state_changed_at' => $now,
                // Undo clears the key so the senior can genuinely re-confirm
                // later within the window.
                'idempotency_key' => null,
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

            // H1: return exactly what the confirm took, not a blanket +1.
            // A confirm that found zero stock decremented nothing, so undoing
            // it must add nothing back.
            if ($medication && $inventoryDelta > 0) {
                Medication::where('id', $medication->id)
                    ->update(['current_stock' => DB::raw("current_stock + {$inventoryDelta}")]);
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
     * H5 — caregiver puts a dose on hold (patient is NPO, pre-op, vomiting).
     *
     * A held dose is a clinical decision, not a lapse: it must never become
     * 'missed' and never contribute to a missed-dose alert.
     */
    public function holdDose(DoseInstance $instance, int $caregiverId, string $reason, ?Carbon $now = null): array
    {
        return $this->applyNonAdministration($instance, 'held', 'caregiver', $caregiverId, $reason, $now);
    }

    /**
     * H5 — senior or caregiver skips a dose, with a reason.
     *
     * Gives the senior an honest way to say "I'm not taking this" instead of
     * leaving the dose to silently rot into 'missed'.
     */
    public function skipDose(
        DoseInstance $instance,
        string $source,
        ?int $actorId,
        string $reason,
        ?Carbon $now = null
    ): array {
        return $this->applyNonAdministration($instance, 'skipped', $source, $actorId, $reason, $now);
    }

    /**
     * Shared transition for the two deliberate non-administration states.
     */
    protected function applyNonAdministration(
        DoseInstance $instance,
        string $targetState,
        string $source,
        ?int $actorId,
        string $reason,
        ?Carbon $now = null
    ): array {
        $now = $now ? $now->copy() : Carbon::now();
        $reason = trim($reason);

        if ($reason === '') {
            return [
                'success' => false,
                'error_code' => 'REASON_REQUIRED',
                'message' => 'Please give a reason so the care team has a record.',
            ];
        }

        return DB::transaction(function () use ($instance, $targetState, $source, $actorId, $reason, $now) {
            /** @var DoseInstance $locked */
            $locked = DoseInstance::where('id', $instance->id)->lockForUpdate()->firstOrFail();

            if ($locked->state === $targetState) {
                return [
                    'success' => true,
                    'status' => $locked->state,
                    'message' => $targetState === 'held' ? 'Dose is already on hold.' : 'Dose is already skipped.',
                    'instance' => $locked,
                ];
            }

            // Holding or skipping a dose already recorded as taken would erase
            // an administration the caregiver has seen. Undo it first.
            if ($locked->isTaken()) {
                return [
                    'success' => false,
                    'error_code' => 'DOSE_ALREADY_TAKEN',
                    'message' => 'This dose is already recorded as taken. Undo it first if that was a mistake.',
                ];
            }

            $locked->update([
                'state' => $targetState,
                'taken_at' => null,
                'source' => $source,
                'actor_id' => $actorId,
                'state_reason' => $reason,
                'state_changed_at' => $now,
                'version' => $locked->version + 1,
            ]);

            return [
                'success' => true,
                'status' => $targetState,
                'message' => $targetState === 'held'
                    ? 'Dose placed on hold.'
                    : 'Dose marked as skipped.',
                'state_reason' => $reason,
                'instance' => $locked,
            ];
        });
    }

    /**
     * Mark a dose as missed (called by scheduler or rules engine).
     *
     * Only a still-pending dose can be missed. A held or skipped dose is a
     * deliberate decision and is left alone.
     */
    public function markMissed(DoseInstance $instance): void
    {
        if (!$instance->isPending()) {
            return;
        }

        $instance->update([
            'state' => 'missed',
            'version' => $instance->version + 1,
        ]);

        $medication = $instance->medication;
        if (!$medication) {
            return;
        }

        $timezone = $instance->timezone ?: config('app.timezone', 'Asia/Manila');
        $localTime = $instance->scheduled_at_utc->copy()->setTimezone($timezone);

        // Patient-facing reminder for every miss.
        $this->notificationService->createMedicationMissedNotification(
            $instance->elderly_id,
            $medication->name,
            $localTime->format('g:i A')
        );

        // Caregiver-facing alert once a run of misses crosses the threshold.
        // Never let alerting failure leave the dose stuck in 'pending' — the
        // state transition above is the authoritative record.
        try {
            $this->rulesService->evaluateMissedDose($instance->fresh());
        } catch (\Throwable $e) {
            Log::error(
                "Missed-dose evaluation failed for dose instance #{$instance->id}: " . $e->getMessage()
            );
        }
    }
}
