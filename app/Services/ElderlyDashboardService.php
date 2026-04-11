<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Checklist;
use App\Models\GoogleFitToken;
use App\Models\HealthMetric;
use App\Models\Medication;
use App\Models\MedicationLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ElderlyDashboardService
{
    /** Required vital types for daily goals tracking */
    private const REQUIRED_VITALS = ['heart_rate', 'blood_pressure', 'sugar_level', 'temperature'];

    public function __construct(protected NotificationService $notificationService)
    {
    }

    /**
     * Assemble all data for the elderly dashboard view.
     */
    public function getDashboardData(int $elderlyId, int $userId): array
    {
        $medications  = $this->getMedicationData($elderlyId);
        $checklists   = $this->getChecklistData($elderlyId);
        $vitals       = $this->getVitalsData($elderlyId);
        $medProgress  = $this->getMedicationProgress($medications['todayMedications'], $medications['medicationLogs']);
        $checkProg    = $this->getChecklistProgress($checklists['todayChecklists']);
        $dailyGoals   = $this->calculateDailyGoals($checkProg, $medProgress, $vitals['vitalsProgress']);
        $gardenInsights = $this->getGardenInsights(
            $elderlyId,
            $medications['medications'],
            $checklists['checklists'],
        );
        $stepsData    = $this->getStepsData($elderlyId);
        $moodData     = $this->getMoodData($elderlyId);
        $upcomingEvents       = $this->getUpcomingEvents($userId);
        $unreadNotifications  = $this->getUnreadNotificationCount($elderlyId);
        $googleFitConnected   = GoogleFitToken::where('user_id', $userId)->exists();

        return [
            'medications'          => $medications['medications'],
            'todayMedications'     => $medications['todayMedications'],
            'medicationLogs'       => $medications['medicationLogs'],
            'checklists'           => $checklists['checklists'],
            'todayChecklists'      => $checklists['todayChecklists'],
            'completedChecklists'  => $checkProg['completed'],
            'totalChecklists'      => $checkProg['total'],
            'checklistProgress'    => $checkProg['progress'],
            'todayVitals'          => $vitals['todayVitals'],
            'recordedVitalTypes'   => $vitals['recordedVitalTypes'],
            'completedVitals'      => $vitals['completedVitals'],
            'totalRequiredVitals'  => $vitals['totalRequiredVitals'],
            'vitalsProgress'       => $vitals['vitalsProgress'],
            'vitalsData'           => $vitals['vitalsData'],
            'stepsData'            => $stepsData,
            'takenMedicationDoses' => $medProgress['taken'],
            'totalMedicationDoses' => $medProgress['total'],
            'medicationProgress'   => $medProgress['progress'],
            'dailyGoalsProgress'   => $dailyGoals,
            'gardenStreakDays'     => $gardenInsights['streakDays'],
            'gardenIsWilting'      => $gardenInsights['isWilting'],
            'gardenMissedCount'    => $gardenInsights['missedCount'],
            'googleFitConnected'   => $googleFitConnected,
            'todayMood'            => $moodData['value'],
            'moodRecordedToday'    => $moodData['recorded'],
            'upcomingEvents'       => $upcomingEvents,
            'unreadNotifications'  => $unreadNotifications,
        ];
    }

    // ------------------------------------------------------------------
    //  Private data-fetching helpers
    // ------------------------------------------------------------------

    private function getMedicationData(int $elderlyId): array
    {
        $medications = Medication::where('elderly_id', $elderlyId)
            ->where('is_active', true)
            ->with('schedules')
            ->get();

        $today = Carbon::today();
        $todayMedications = $medications->filter(function (Medication $med) use ($today) {
            return $med->isScheduledForDate($today);
        });

        $medicationLogs = MedicationLog::where('elderly_id', $elderlyId)
            ->whereDate('scheduled_time', Carbon::today())
            ->get()
            ->keyBy(fn ($log) => $this->doseLogKey($log->medication_id, $log->scheduled_time));

        return compact('medications', 'todayMedications', 'medicationLogs');
    }

    private function getChecklistData(int $elderlyId): array
    {
        $checklists = Checklist::where('elderly_id', $elderlyId)->get();

        $todayChecklists = $checklists
            ->filter(fn (Checklist $checklist) => $checklist->due_date?->isToday())
            ->sortBy('due_time')
            ->values();

        return compact('checklists', 'todayChecklists');
    }

    private function getVitalsData(int $elderlyId): array
    {
        $todayVitals = HealthMetric::where('elderly_id', $elderlyId)
            ->whereDate('measured_at', Carbon::today())
            ->get();
        $vitalsByType = $todayVitals->sortByDesc('measured_at')->groupBy('type');

        $recordedVitalTypes = $todayVitals->pluck('type')->unique()->toArray();

        $totalRequiredVitals = count(self::REQUIRED_VITALS);
        $completedVitals = count(array_intersect(self::REQUIRED_VITALS, $recordedVitalTypes));
        $vitalsProgress = $totalRequiredVitals > 0 ? round(($completedVitals / $totalRequiredVitals) * 100) : 0;

        $vitalsData = [];
        foreach (self::REQUIRED_VITALS as $vitalType) {
            $latestMetric = $vitalsByType->get($vitalType)?->first();
            $vitalsData[$vitalType] = [
                'recorded'   => $latestMetric !== null,
                'value'      => $latestMetric?->value,
                'value_text' => $latestMetric?->value_text,
                'unit'       => $latestMetric?->unit,
                'measured_at'=> $latestMetric?->measured_at,
                'source'     => $latestMetric?->source,
            ];
        }

        return compact('todayVitals', 'recordedVitalTypes', 'totalRequiredVitals', 'completedVitals', 'vitalsProgress', 'vitalsData');
    }

    private function getMedicationProgress($todayMedications, Collection $medicationLogs): array
    {
        $total = 0;
        $taken = 0;
        $today = Carbon::today();

        foreach ($todayMedications as $med) {
            $times = $med->scheduleTimesForDate($today);
            $total += count($times);
            foreach ($times as $time) {
                $logKey = $this->doseLogKey($med->id, $time);
                if (isset($medicationLogs[$logKey]) && $medicationLogs[$logKey]->is_taken) {
                    $taken++;
                }
            }
        }

        $progress = $total > 0 ? round(($taken / $total) * 100) : 0;

        return compact('total', 'taken', 'progress');
    }

    private function getChecklistProgress($todayChecklists): array
    {
        $completed = $todayChecklists->where('is_completed', true)->count();
        $total     = $todayChecklists->count();
        $progress  = $total > 0 ? round(($completed / $total) * 100) : 0;

        return compact('completed', 'total', 'progress');
    }

    private function calculateDailyGoals(array $checkProg, array $medProg, int $vitalsProgress): int
    {
        $totalWeight     = 0;
        $weightedProgress = 0;

        if ($checkProg['total'] > 0) {
            $totalWeight     += 40;
            $weightedProgress += $checkProg['progress'] * 40;
        }
        if ($medProg['total'] > 0) {
            $totalWeight     += 40;
            $weightedProgress += $medProg['progress'] * 40;
        }
        if (count(self::REQUIRED_VITALS) > 0) {
            $totalWeight     += 20;
            $weightedProgress += $vitalsProgress * 20;
        }

        return $totalWeight > 0 ? round($weightedProgress / $totalWeight) : 0;
    }

    /**
     * Build Garden of Wellness state: streak progress + wilting signal.
     */
    private function getGardenInsights(int $elderlyId, Collection $activeMedications, Collection $allChecklists): array
    {
        $today = Carbon::today();
        $start = $today->copy()->subDays(6);

        $takenLogs = MedicationLog::where('elderly_id', $elderlyId)
            ->whereDate('scheduled_time', '>=', $start)
            ->whereDate('scheduled_time', '<=', $today)
            ->where('is_taken', true)
            ->get()
            ->groupBy(fn ($log) => $log->medication_id . '_' . $log->scheduled_time->format('Y-m-d'));

        $checklistsByDay = $allChecklists
            ->filter(fn ($task) => $task->due_date && $task->due_date->between($start, $today))
            ->groupBy(fn ($task) => optional($task->due_date)->format('Y-m-d'));

        $vitalsByDay = HealthMetric::where('elderly_id', $elderlyId)
            ->whereIn('type', self::REQUIRED_VITALS)
            ->whereDate('measured_at', '>=', $start)
            ->whereDate('measured_at', '<=', $today)
            ->get()
            ->groupBy(fn ($metric) => $metric->measured_at->format('Y-m-d'));

        $streakDays = 0;
        for ($cursor = $today->copy(); $cursor->gte($start); $cursor->subDay()) {
            $dayProgress = $this->calculateDayGoalProgress(
                $cursor,
                $activeMedications,
                $takenLogs,
                $checklistsByDay,
                $vitalsByDay
            );

            if ($dayProgress >= 100) {
                $streakDays++;
                continue;
            }

            break;
        }

        $missedDoses = $this->countMissedDosesToday($today, $activeMedications, $takenLogs);
        $overdueTasks = $this->countOverdueTasks($elderlyId);
        $missedCount = $missedDoses + $overdueTasks;

        return [
            'streakDays' => $streakDays,
            'isWilting' => $missedCount > 0,
            'missedCount' => $missedCount,
        ];
    }

    private function calculateDayGoalProgress(
        Carbon $date,
        Collection $activeMedications,
        Collection $takenLogs,
        Collection $checklistsByDay,
        Collection $vitalsByDay,
    ): int {
        $dateKey = $date->format('Y-m-d');

        $tasksForDay = $checklistsByDay->get($dateKey, collect());
        $taskTotal = $tasksForDay->count();
        $taskCompleted = $tasksForDay->where('is_completed', true)->count();
        $taskProgress = $taskTotal > 0 ? round(($taskCompleted / $taskTotal) * 100) : 0;

        $doseTotal = 0;
        $doseTaken = 0;
        foreach ($activeMedications as $medication) {
            if (!$medication->isScheduledForDate($date)) {
                continue;
            }

            $times = $medication->scheduleTimesForDate($date);
            $scheduledDoses = count($times);
            if ($scheduledDoses === 0) {
                continue;
            }

            $doseTotal += $scheduledDoses;

            $logKey = $medication->id . '_' . $dateKey;
            $takenForDay = $takenLogs->get($logKey)?->count() ?? 0;
            $doseTaken += min($takenForDay, $scheduledDoses);
        }

        $medicationProgress = $doseTotal > 0 ? round(($doseTaken / $doseTotal) * 100) : 0;

        $recordedTypes = collect($vitalsByDay->get($dateKey, collect()))
            ->pluck('type')
            ->unique();
        $vitalsProgress = count(self::REQUIRED_VITALS) > 0
            ? round(($recordedTypes->count() / count(self::REQUIRED_VITALS)) * 100)
            : 0;

        return $this->calculateDailyGoals(
            ['total' => $taskTotal, 'progress' => $taskProgress],
            ['total' => $doseTotal, 'progress' => $medicationProgress],
            $vitalsProgress
        );
    }

    private function countMissedDosesToday(Carbon $today, Collection $activeMedications, Collection $takenLogs): int
    {
        $now = Carbon::now();
        $timezone = config('app.timezone', 'Asia/Manila');
        $todayKey = $today->format('Y-m-d');
        $missed = 0;

        foreach ($activeMedications as $medication) {
            if (!$medication->isScheduledForDate($today)) {
                continue;
            }

            $scheduledTimes = $medication->scheduleTimesForDate($today);
            $takenForDay = collect($takenLogs->get($medication->id . '_' . $todayKey, collect()))
                ->map(fn ($log) => $log->scheduled_time->format('H:i'));

            foreach ($scheduledTimes as $time) {
                $scheduledDateTime = Carbon::parse($today->toDateString() . ' ' . $time, $timezone);
                $windowEnd = $scheduledDateTime->copy()->addHour();
                $alreadyTaken = $takenForDay->contains(Carbon::parse($time)->format('H:i'));

                if ($now->greaterThan($windowEnd) && !$alreadyTaken) {
                    $missed++;
                }
            }
        }

        return $missed;
    }

    private function countOverdueTasks(int $elderlyId): int
    {
        $today = Carbon::today();
        $currentTime = Carbon::now()->format('H:i:s');

        return Checklist::where('elderly_id', $elderlyId)
            ->where('is_completed', false)
            ->where(function ($query) use ($today, $currentTime) {
                $query->whereDate('due_date', '<', $today)
                    ->orWhere(function ($todayQuery) use ($today, $currentTime) {
                        $todayQuery->whereDate('due_date', $today)
                            ->whereNotNull('due_time')
                            ->where('due_time', '<', $currentTime);
                    });
            })
            ->count();
    }

    private function getStepsData(int $elderlyId): ?array
    {
        $stepsMetric = HealthMetric::where('elderly_id', $elderlyId)
            ->where('type', 'steps')
            ->whereDate('measured_at', today())
            ->orderBy('measured_at', 'desc')
            ->first();

        if (!$stepsMetric) {
            return null;
        }

        return [
            'value'     => (int) $stepsMetric->value,
            'goal'      => 6000,
            'source'    => $stepsMetric->source,
            'synced_at' => $stepsMetric->measured_at,
        ];
    }

    private function getMoodData(int $elderlyId): array
    {
        $moodMetric = HealthMetric::where('elderly_id', $elderlyId)
            ->where('type', 'mood')
            ->whereDate('measured_at', Carbon::today())
            ->first();

        return [
            'value' => $moodMetric ? (int) $moodMetric->value : 3,
            'recorded' => $moodMetric !== null,
        ];
    }

    private function getUpcomingEvents(int $userId): array|\Illuminate\Support\Collection
    {
        try {
            if (class_exists(CalendarEvent::class)) {
                return CalendarEvent::where('user_id', $userId)
                    ->where('start_time', '>=', now())
                    ->orderBy('start_time', 'asc')
                    ->take(3)
                    ->get();
            }
        } catch (\Exception $e) {
            // table/model issues
        }

        return [];
    }

    private function getUnreadNotificationCount(int $elderlyId): int
    {
        return $this->notificationService->getUnreadCount($elderlyId);
    }

    private function doseLogKey(int $medicationId, mixed $time): string
    {
        return $medicationId . '_' . Carbon::parse($time)->format('H:i');
    }
}
