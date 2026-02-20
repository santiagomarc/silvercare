<?php

namespace App\Services;

use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MedicationService
{
    /**
     * Add a new medication schedule (Caregiver CRUD)
     */
    public function addMedicationSchedule(array $data): Medication
    {
        return Medication::create([
            'elderly_id' => $data['elderly_id'],
            'caregiver_id' => $data['caregiver_id'],
            'name' => $data['name'],
            'dosage' => $data['dosage'],
            'instructions' => $data['instructions'] ?? null,
            'days_of_week' => $data['days_of_week'] ?? null, // [1,3,5] for Mon,Wed,Fri
            'specific_dates' => $data['specific_dates'] ?? null, // ['2025-12-25'] for one-time
            'times_of_day' => $data['times_of_day'], // ['09:00', '21:00']
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Update medication schedule
     */
    public function updateMedicationSchedule(Medication $medication, array $data): Medication
    {
        $medication->update([
            'name' => $data['name'] ?? $medication->name,
            'dosage' => $data['dosage'] ?? $medication->dosage,
            'instructions' => $data['instructions'] ?? $medication->instructions,
            'days_of_week' => $data['days_of_week'] ?? $medication->days_of_week,
            'specific_dates' => $data['specific_dates'] ?? $medication->specific_dates,
            'times_of_day' => $data['times_of_day'] ?? $medication->times_of_day,
            'start_date' => $data['start_date'] ?? $medication->start_date,
            'end_date' => $data['end_date'] ?? $medication->end_date,
            'is_active' => $data['is_active'] ?? $medication->is_active,
        ]);

        return $medication->fresh();
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
            ->with(['elderly', 'caregiver', 'logs' => function ($query) {
                $query->whereDate('scheduled_time', Carbon::today());
            }])
            ->get();
    }

    /**
     * Get medication schedules for elderly (caregiver view)
     */
    public function getMedicationSchedulesForElderly(int $elderlyProfileId): Collection
    {
        return Medication::where('elderly_id', $elderlyProfileId)
            ->with(['elderly', 'caregiver'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get today's doses for elderly user
     */
    public function getTodaysDoses(int $elderlyProfileId): Collection
    {
        $today = Carbon::today();
        $dayOfWeek = $today->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.

        return Medication::where('elderly_id', $elderlyProfileId)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->where(function ($query) use ($dayOfWeek, $today) {
                // Recurring: check days_of_week
                $query->whereJsonContains('days_of_week', (string)$dayOfWeek)
                    // OR one-time: check specific_dates
                    ->orWhereJsonContains('specific_dates', $today->format('Y-m-d'));
            })
            ->with('logs')
            ->get();
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

