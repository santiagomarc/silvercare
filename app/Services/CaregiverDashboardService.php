<?php

namespace App\Services;

use App\Models\Checklist;
use App\Models\HealthMetric;
use App\Models\MedicationLog;
use App\Models\Notification;
use App\Models\UserProfile;
use App\Presenters\HealthMetricPresenter;
use App\Presenters\NotificationPresenter;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CaregiverDashboardService
{
    /**
     * Fetch today's mood and latest vitals snapshots for an elderly profile.
     */
    public function getTodayVitalsAndMood(int $elderlyId): array
    {
        $today = Carbon::today();

        $mood = HealthMetric::where('elderly_id', $elderlyId)
            ->where('type', 'mood')
            ->whereDate('measured_at', $today)
            ->latest('measured_at')
            ->first();

        $todayMetrics = HealthMetric::where('elderly_id', $elderlyId)
            ->whereIn('type', ['heart_rate', 'blood_pressure', 'sugar_level', 'temperature'])
            ->whereDate('measured_at', $today)
            ->orderBy('measured_at', 'desc')
            ->get()
            ->unique('type')
            ->keyBy('type');

        $heartRate = $todayMetrics->get('heart_rate');
        $bloodPressure = $todayMetrics->get('blood_pressure');
        $sugarLevel = $todayMetrics->get('sugar_level');
        $temperature = $todayMetrics->get('temperature');

        $vitals = [
            'heart_rate' => $heartRate ? [
                'metric' => $heartRate,
                'status' => HealthMetricPresenter::getHeartRateStatus($heartRate->value),
            ] : null,
            'blood_pressure' => $bloodPressure ? [
                'metric' => $bloodPressure,
                'status' => HealthMetricPresenter::getBloodPressureStatus($bloodPressure->value_text),
            ] : null,
            'sugar_level' => $sugarLevel ? [
                'metric' => $sugarLevel,
                'status' => HealthMetricPresenter::getSugarLevelStatus($sugarLevel->value),
            ] : null,
            'temperature' => $temperature ? [
                'metric' => $temperature,
                'status' => HealthMetricPresenter::getTemperatureStatus($temperature->value),
            ] : null,
        ];

        return compact('mood', 'vitals');
    }

    /**
     * Get recent caregiver-facing activity cards for the last 7 days.
     */
    public function getRecentActivity(int $elderlyId): Collection
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $notifications = Notification::where('elderly_id', $elderlyId)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return NotificationPresenter::toCaregiverActivity($notifications)
            ->sortByDesc('timestamp')
            ->take(30)
            ->values();
    }

    /**
     * Build today's caregiver summary stats for medications, checklists and vitals.
     */
    public function getStats(UserProfile $elderly): array
    {
        $today = Carbon::today();
        $dayOfWeek = $today->format('l');

        $todaysMeds = $elderly->trackedMedications()
            ->where('is_active', true)
            ->get();

        $applicableMeds = $todaysMeds->filter(fn ($med) => in_array($dayOfWeek, $med->days_of_week ?? []));
        $medIds = $applicableMeds->pluck('id');

        $totalDosesToday = $applicableMeds->sum(fn ($med) => count($med->times_of_day ?? []));

        $takenDosesToday = MedicationLog::whereIn('medication_id', $medIds)
            ->whereDate('scheduled_time', $today)
            ->where('is_taken', true)
            ->count();

        $medicationAdherence = $totalDosesToday > 0
            ? round(($takenDosesToday / $totalDosesToday) * 100)
            : null;

        $todaysTasks = Checklist::where('elderly_id', $elderly->id)
            ->whereDate('due_date', $today)
            ->get();

        $totalTasks = $todaysTasks->count();
        $completedTasks = $todaysTasks->where('is_completed', true)->count();

        $taskCompletion = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100)
            : null;

        $vitalsToday = HealthMetric::where('elderly_id', $elderly->id)
            ->whereIn('type', ['heart_rate', 'blood_pressure', 'sugar_level', 'temperature'])
            ->whereDate('measured_at', $today)
            ->distinct('type')
            ->count('type');

        return [
            'medication_adherence' => $medicationAdherence,
            'doses_taken' => $takenDosesToday,
            'doses_total' => $totalDosesToday,
            'task_completion' => $taskCompletion,
            'tasks_completed' => $completedTasks,
            'tasks_total' => $totalTasks,
            'vitals_recorded' => $vitalsToday,
            'vitals_total' => 4,
        ];
    }
}
