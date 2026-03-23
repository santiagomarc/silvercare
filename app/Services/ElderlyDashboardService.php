<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Checklist;
use App\Models\GoogleFitToken;
use App\Models\HealthMetric;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ElderlyDashboardService
{
    /** Required vital types for daily goals tracking */
    private const REQUIRED_VITALS = ['heart_rate', 'blood_pressure', 'sugar_level', 'temperature'];

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
        $stepsData    = $this->getStepsData($elderlyId);
        $todayMood    = $this->getMoodData($elderlyId);
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
            'googleFitConnected'   => $googleFitConnected,
            'todayMood'            => $todayMood,
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

        $todayChecklists = Checklist::where('elderly_id', $elderlyId)
            ->whereDate('due_date', Carbon::today())
            ->orderBy('due_time')
            ->get();

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

    private function getMoodData(int $elderlyId): int
    {
        $moodMetric = HealthMetric::where('elderly_id', $elderlyId)
            ->where('type', 'mood')
            ->whereDate('measured_at', Carbon::today())
            ->first();

        return $moodMetric ? (int) $moodMetric->value : 3;
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
        return Notification::where('elderly_id', $elderlyId)
            ->where('is_read', false)
            ->count();
    }

    private function doseLogKey(int $medicationId, mixed $time): string
    {
        return $medicationId . '_' . Carbon::parse($time)->format('H:i');
    }
}
