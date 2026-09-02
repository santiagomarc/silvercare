<?php

namespace App\Services;

use App\Models\DoseInstance;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\PrescriptionRevision;
use App\Models\MedicationSchedule;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MedicationService
{
    /**
     * Add a new medication schedule (Caregiver CRUD)
     */
    public function addMedicationSchedule(array $data): Medication
    {
        return DB::transaction(function () use ($data) {
            $medication = Medication::create([
                'elderly_id' => $data['elderly_id'],
                'caregiver_id' => $data['caregiver_id'],
                'name' => $data['name'],
                'dosage' => $data['dosage'],
                'dosage_unit' => $data['dosage_unit'] ?? 'mg',
                'instructions' => $data['instructions'] ?? null,
                'days_of_week' => $data['days_of_week'] ?? null,
                'specific_dates' => $data['specific_dates'] ?? null,
                'times_of_day' => $data['times_of_day'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'track_inventory' => $data['track_inventory'] ?? false,
                'current_stock' => $data['current_stock'] ?? 0,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
            ]);

            $this->syncSchedules($medication, $data);

            // H4: materialise the upcoming doses now. Waiting for the nightly
            // 00:05 job would leave a medication added at 09:00 invisible to
            // the caregiver briefing, adherence view and escalation sweep for
            // the rest of the day.
            $this->regenerateUpcomingInstances($medication->fresh('schedules'));

            return $medication->load('schedules');
        });
    }

    /**
     * Update medication schedule
     */
    public function updateMedicationSchedule(Medication $medication, array $data): Medication
    {
        return DB::transaction(function () use ($medication, $data) {
            // H3: capture the prior values before mutating, so the caregiver
            // record shows what a prescription looked like before each edit.
            $before = $this->revisionSnapshot($medication);

            $medication->update([
                'name' => $data['name'] ?? $medication->name,
                'dosage' => $data['dosage'] ?? $medication->dosage,
                'dosage_unit' => $data['dosage_unit'] ?? $medication->dosage_unit,
                'instructions' => $data['instructions'] ?? $medication->instructions,
                'days_of_week' => $data['days_of_week'] ?? $medication->days_of_week,
                'specific_dates' => $data['specific_dates'] ?? $medication->specific_dates,
                'times_of_day' => $data['times_of_day'] ?? $medication->times_of_day,
                'start_date' => $data['start_date'] ?? $medication->start_date,
                'end_date' => $data['end_date'] ?? $medication->end_date,
                'is_active' => $data['is_active'] ?? $medication->is_active,
                'track_inventory' => $data['track_inventory'] ?? $medication->track_inventory,
                'current_stock' => $data['current_stock'] ?? $medication->current_stock,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? $medication->low_stock_threshold,
            ]);

            if ($this->shouldSyncSchedules($data)) {
                $this->syncSchedules($medication, $data);
            }

            $medication->refresh()->load('schedules');
            $after = $this->revisionSnapshot($medication);

            if ($before !== $after) {
                PrescriptionRevision::create([
                    'medication_id' => $medication->id,
                    'changed_by' => Auth::id(),
                    'old_values' => $before,
                    'new_values' => $after,
                ]);

                // H3: regenerate forward only. History is never rewritten — a
                // dose already taken, missed, held or skipped is what actually
                // happened, and a later prescription edit must not restate it.
                $this->regenerateUpcomingInstances($medication);
            }

            return $medication->load('schedules');
        });
    }

    /**
     * The prescription fields worth auditing. Stock level is deliberately
     * excluded: it changes on every dose and would bury real schedule edits.
     *
     * @return array<string, mixed>
     */
    protected function revisionSnapshot(Medication $medication): array
    {
        return [
            'name' => $medication->name,
            'dosage' => $medication->dosage,
            'dosage_unit' => $medication->dosage_unit,
            'instructions' => $medication->instructions,
            'days_of_week' => $medication->days_of_week,
            'specific_dates' => $medication->specific_dates,
            'times_of_day' => $medication->times_of_day,
            'start_date' => $medication->start_date?->toDateString(),
            'end_date' => $medication->end_date?->toDateString(),
            'is_active' => (bool) $medication->is_active,
            'schedules' => $medication->schedules
                ->map(fn ($s) => [
                    'schedule_type' => $s->schedule_type,
                    'time_of_day' => substr((string) $s->time_of_day, 0, 5),
                    'day_of_week' => $s->day_of_week ?? null,
                    'specific_date' => $s->specific_date ?? null,
                ])
                ->sortBy(fn ($s) => $s['schedule_type'] . '|' . $s['time_of_day'])
                ->values()
                ->all(),
        ];
    }

    /**
     * Drop future doses that no longer match the schedule and regenerate them.
     *
     * Only *pending* instances from now forward are removed. Anything already
     * resolved — taken, missed, held, skipped — is history and stays untouched.
     */
    protected function regenerateUpcomingInstances(Medication $medication): void
    {
        $timezone = $medication->elderly?->timezone ?: config('app.timezone', 'Asia/Manila');
        $from = Carbon::now($timezone);

        DoseInstance::where('medication_id', $medication->id)
            ->where('state', 'pending')
            ->where('scheduled_at_utc', '>=', $from->copy()->setTimezone('UTC'))
            ->delete();

        if ($medication->is_active) {
            app(DoseInstanceGeneratorService::class)->generateForMedication(
                $medication,
                $from,
                (int) config('medications.generation_horizon_days', 7)
            );
        }
    }

    /**
     * Delete medication schedule
     */
    public function deleteMedicationSchedule(int $medicationId): bool
    {
        $medication = Medication::findOrFail($medicationId);
        return $medication->delete();
    }

    /**
     * Get all active medication schedules for elderly (home screen)
     */
    public function getActiveMedicationSchedules(int $elderlyProfileId): Collection
    {
        return Medication::where('elderly_id', $elderlyProfileId)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', Carbon::today())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', Carbon::today());
            })
            ->with(['elderly', 'caregiver', 'schedules', 'logs' => function ($query) {
                $query->whereDate('scheduled_time', Carbon::today());
            }])
            ->get();
    }

    /**
     * Get medication schedules for elderly (caregiver view)
     */
    public function getMedicationSchedulesForElderly(int $elderlyProfileId, int $limit = 100): Collection
    {
        return Medication::where('elderly_id', $elderlyProfileId)
            ->with(['elderly', 'caregiver', 'schedules'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get today's doses for elderly user
     */
    public function getTodaysDoses(int $elderlyProfileId): Collection
    {
        $today = Carbon::today();
        return Medication::where('elderly_id', $elderlyProfileId)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->with(['schedules', 'logs'])
            ->get()
            ->filter(fn (Medication $medication) => $medication->isScheduledForDate($today))
            ->values();
    }

    /**
     * Mark medication dose as taken
     */
    public function markDoseAsTaken(int $medicationId, string $scheduledTime): MedicationLog
    {
        $medication = Medication::findOrFail($medicationId);

        return MedicationLog::create([
            'medication_id' => $medicationId,
            'elderly_id' => $medication->elderly_id,
            'scheduled_time' => $scheduledTime,
            'is_taken' => true,
            'taken_at' => Carbon::now(),
        ]);
    }

    /**
     * Get medication logs for date range
     */
    public function getMedicationLogs(int $elderlyProfileId, Carbon $startDate, Carbon $endDate): Collection
    {
        return MedicationLog::where('elderly_id', $elderlyProfileId)
            ->whereBetween('scheduled_time', [$startDate, $endDate])
            ->with('medication')
            ->orderBy('scheduled_time', 'desc')
            ->get();
    }

    /**
     * Get missed doses (not taken within grace period)
     */
    public function getMissedDoses(int $elderlyProfileId, int $graceMinutes = 15): Collection
    {
        $now = Carbon::now();
        $cutoffTime = $now->copy()->subMinutes($graceMinutes);

        return MedicationLog::where('elderly_id', $elderlyProfileId)
            ->where('is_taken', false)
            ->where('scheduled_time', '<=', $cutoffTime)
            ->whereDate('scheduled_time', Carbon::today())
            ->with('medication')
            ->get();
    }

    private function syncSchedules(Medication $medication, array $data): void
    {
        $scheduleType = $data['schedule_type'] ?? $medication->primaryScheduleType();
        $times = collect($data['times_of_day'] ?? [])
            ->filter(fn ($time) => is_string($time) && $time !== '')
            ->unique()
            ->sort()
            ->values();

        $daysOfWeek = collect($data['days_of_week'] ?? [])
            ->filter(fn ($day) => is_string($day) && $day !== '')
            ->unique()
            ->values()
            ->all();

        $specificDates = collect($data['specific_dates'] ?? [])
            ->filter(fn ($date) => is_string($date) && $date !== '')
            ->unique()
            ->sort()
            ->values();

        $medication->schedules()->delete();

        if ($times->isEmpty()) {
            return;
        }

        $rows = [];
        foreach ($times as $time) {
            if ($scheduleType === 'specific_date') {
                foreach ($specificDates as $specificDate) {
                    $rows[] = [
                        'schedule_type' => 'specific_date',
                        'days_of_week' => null,
                        'specific_date' => $specificDate,
                        'time_of_day' => $time,
                    ];
                }

                continue;
            }

            $rows[] = [
                'schedule_type' => $scheduleType === 'weekly' ? 'weekly' : 'daily',
                'days_of_week' => $scheduleType === 'weekly' ? $daysOfWeek : null,
                'specific_date' => null,
                'time_of_day' => $time,
            ];
        }

        if (!empty($rows)) {
            $medication->schedules()->createMany($rows);
        }

        // Keep legacy JSON fields in sync during transition.
        $legacyDays = $scheduleType === 'weekly' ? $daysOfWeek : null;
        $legacyDates = $scheduleType === 'specific_date' ? $specificDates->all() : null;

        $medication->forceFill([
            'frequency' => $scheduleType,
            'days_of_week' => $legacyDays,
            'specific_dates' => $legacyDates,
            'times_of_day' => $times->all(),
        ])->saveQuietly();
    }

    private function shouldSyncSchedules(array $data): bool
    {
        foreach (['schedule_type', 'times_of_day', 'days_of_week', 'specific_dates'] as $field) {
            if (array_key_exists($field, $data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate adherence rate for date range
     */
    public function calculateAdherence(int $elderlyProfileId, Carbon $startDate, Carbon $endDate): array
    {
        $totalLogs = MedicationLog::where('elderly_id', $elderlyProfileId)
            ->whereBetween('scheduled_time', [$startDate, $endDate])
            ->count();

        $takenLogs = MedicationLog::where('elderly_id', $elderlyProfileId)
            ->whereBetween('scheduled_time', [$startDate, $endDate])
            ->where('is_taken', true)
            ->count();

        $missedLogs = $totalLogs - $takenLogs;
        $adherenceRate = $totalLogs > 0 ? ($takenLogs / $totalLogs) * 100 : 0;

        return [
            'total' => $totalLogs,
            'taken' => $takenLogs,
            'missed' => $missedLogs,
            'adherence_rate' => round($adherenceRate, 2),
        ];
    }
}

