<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HealthMetric;
use App\Models\MedicationLog;
use App\Models\Checklist;
use App\Models\Notification;
use Carbon\Carbon;

class CaregiverDashboardController extends Controller
{
    public function index()
    {
        $caregiver = Auth::user()->profile;
        
        // Ensure the user has a profile
        if (!$caregiver) {
            return redirect()->route('profile.complete');
        }

        $elderly = $caregiver->elderly;

        if (!$elderly) {
            return view('caregiver.dashboard', [
                'elderly' => null,
                'elderlyUser' => null,
                'mood' => null,
                'vitals' => [],
                'recentActivity' => collect(),
                'stats' => [],
            ]);
        }

        // Get the elderly user
        $elderlyUser = $elderly->user;

        // Fetch TODAY's latest metrics only (like Flutter version)
        $today = Carbon::today();
        
        $mood = HealthMetric::where('elderly_id', $elderly->id)
            ->where('type', 'mood')
            ->whereDate('measured_at', $today)
            ->latest('measured_at')
            ->first();
        
        $heartRate = HealthMetric::where('elderly_id', $elderly->id)
            ->where('type', 'heart_rate')
            ->whereDate('measured_at', $today)
            ->latest('measured_at')
            ->first();
            
        $bloodPressure = HealthMetric::where('elderly_id', $elderly->id)
            ->where('type', 'blood_pressure')
            ->whereDate('measured_at', $today)
            ->latest('measured_at')
            ->first();
            
        $sugarLevel = HealthMetric::where('elderly_id', $elderly->id)
            ->where('type', 'sugar_level')
            ->whereDate('measured_at', $today)
            ->latest('measured_at')
            ->first();
            
        $temperature = HealthMetric::where('elderly_id', $elderly->id)
            ->where('type', 'temperature')
            ->whereDate('measured_at', $today)
            ->latest('measured_at')
            ->first();

        $vitals = [
            'heart_rate' => $heartRate ? [
                'metric' => $heartRate,
                'status' => \App\Presenters\HealthMetricPresenter::getHeartRateStatus($heartRate->value),
            ] : null,
            'blood_pressure' => $bloodPressure ? [
                'metric' => $bloodPressure,
                'status' => \App\Presenters\HealthMetricPresenter::getBloodPressureStatus($bloodPressure->value_text),
            ] : null,
            'sugar_level' => $sugarLevel ? [
                'metric' => $sugarLevel,
                'status' => \App\Presenters\HealthMetricPresenter::getSugarLevelStatus($sugarLevel->value),
            ] : null,
            'temperature' => $temperature ? [
                'metric' => $temperature,
                'status' => \App\Presenters\HealthMetricPresenter::getTemperatureStatus($temperature->value),
            ] : null,
        ];

        // Get recent activity (last 7 days)
        $recentActivity = $this->getRecentActivity($elderly->id);

        // Get summary stats
        $stats = $this->getStats($elderly);

        // Parse medical conditions
        $conditions = $elderly->medical_conditions ?? [];
        if (is_string($conditions)) { $conditions = json_decode($conditions, true) ?? []; }
        
        $medications = $elderly->medications ?? [];
        if (is_string($medications)) { $medications = json_decode($medications, true) ?? []; }
        
        $allergies = $elderly->allergies ?? [];
        if (is_string($allergies)) { $allergies = json_decode($allergies, true) ?? []; }
        
        // Fallback to legacy medical_info field if dedicated columns are empty
        $medicalInfo = $elderly->medical_info ?? [];
        if (is_string($medicalInfo)) { $medicalInfo = json_decode($medicalInfo, true) ?? []; }
        
        if (empty($conditions) && !empty($medicalInfo['conditions'])) {
            $conditions = $medicalInfo['conditions'];
        }
        if (empty($medications) && !empty($medicalInfo['medications'])) {
            $medications = $medicalInfo['medications'];
        }
        if (empty($allergies) && !empty($medicalInfo['allergies'])) {
            $allergies = $medicalInfo['allergies'];
        }

        return view('caregiver.dashboard', compact('elderly', 'elderlyUser', 'mood', 'vitals', 'recentActivity', 'stats', 'conditions', 'medications', 'allergies'));
    }

    /**
     * Get recent activity for the elderly (including notifications)
     */
    private function getRecentActivity($elderlyId)
    {
        $activities = collect();
        $sevenDaysAgo = Carbon::now()->subDays(7);

        // Get elder's notifications (these are important alerts for caregivers)
        $notifications = Notification::where('elderly_id', $elderlyId)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        // Map notification types to icons and colors for caregiver view
        $notificationConfig = [
            'medication_taken' => ['icon' => '💊', 'color' => 'green'],
            'medication_taken_late' => ['icon' => '⚠️', 'color' => 'amber'],
            'medication_missed' => ['icon' => '❌', 'color' => 'red'],
            'task_completed' => ['icon' => '✅', 'color' => 'green'],
            'vitals_recorded' => ['icon' => '📊', 'color' => 'blue'],
            'daily_reminder' => ['icon' => '🔔', 'color' => 'blue'],
            'health_alert' => ['icon' => '⚠️', 'color' => 'amber'],
        ];

        foreach ($notifications as $notification) {
            $config = $notificationConfig[$notification->type] ?? ['icon' => '🔔', 'color' => 'gray'];
            
            // Adapt the notification message for caregiver context
            $caregiverTitle = $this->adaptNotificationForCaregiver($notification);
            
            $activities->push([
                'type' => 'notification_' . $notification->type,
                'title' => $caregiverTitle,
                'subtitle' => $this->formatNotificationSubtitle($notification),
                'timestamp' => $notification->created_at,
                'icon' => $config['icon'],
                'color' => $config['color'],
                'severity' => $notification->severity,
            ]);
        }

        // Sort by timestamp and take the most recent 30
        return $activities->sortByDesc('timestamp')->take(30)->values();
    }

    /**
     * Adapt notification message for caregiver context
     */
    private function adaptNotificationForCaregiver(Notification $notification): string
    {
        $elderName = $notification->elderly?->user?->name ?? 'Patient';
        $firstName = explode(' ', $elderName)[0];
        
        switch ($notification->type) {
            case 'medication_taken':
                $medName = $notification->metadata['medicationName'] ?? 'medication';
                return "{$firstName} took {$medName}";
                
            case 'medication_taken_late':
                $medName = $notification->metadata['medicationName'] ?? 'medication';
                return "{$firstName} took {$medName} (late)";
                
            case 'medication_missed':
                $medName = $notification->metadata['medicationName'] ?? 'medication';
                return "{$firstName} missed {$medName}";
                
            case 'task_completed':
                $taskName = $notification->metadata['taskName'] ?? 'a task';
                return "{$firstName} completed {$taskName}";
                
            case 'vitals_recorded':
                $vitalType = $notification->metadata['vitalType'] ?? 'vital';
                return "{$firstName} recorded {$vitalType}";
                
            case 'daily_reminder':
                return "Reminder sent to {$firstName}";
                
            case 'health_alert':
                return "Health alert for {$firstName}";
                
            default:
                // Use the original title but prefix with patient name
                return "{$firstName}: " . $notification->title;
        }
    }

    /**
     * Format notification subtitle with relevant details
     */
    private function formatNotificationSubtitle(Notification $notification): string
    {
        $metadata = $notification->metadata ?? [];
        
        switch ($notification->type) {
            case 'medication_taken':
            case 'medication_taken_late':
                if (isset($metadata['takenAt'])) {
                    return Carbon::parse($metadata['takenAt'])->format('g:i A');
                }
                return 'Dose recorded';
                
            case 'medication_missed':
                return $metadata['scheduledTime'] ?? 'Scheduled dose';
                
            case 'vitals_recorded':
                $value = $metadata['value'] ?? '';
                $type = $metadata['vitalType'] ?? '';
                return $value ? "{$value}" : ucfirst(str_replace('_', ' ', $type));
                
            case 'task_completed':
                return ucfirst($metadata['category'] ?? 'Task');
                
            default:
                // Return severity-based subtitle
                return ucfirst($notification->severity);
        }
    }

    /**
     * Get summary stats for the elderly
     */
    private function getStats($elderly)
    {
        $today = Carbon::today();

        // Today's medication adherence
        $todaysMeds = $elderly->trackedMedications()
            ->where('is_active', true)
            ->get();
        
        $totalDosesToday = 0;
        $takenDosesToday = 0;
        $dayOfWeek = $today->format('l');
        
        foreach ($todaysMeds as $med) {
            if (in_array($dayOfWeek, $med->days_of_week ?? [])) {
                $doseCount = count($med->times_of_day ?? []);
                $totalDosesToday += $doseCount;
                
                $takenToday = MedicationLog::where('medication_id', $med->id)
                    ->whereDate('scheduled_time', $today)
                    ->where('is_taken', true)
                    ->count();
                $takenDosesToday += $takenToday;
            }
        }
        
        $medicationAdherence = $totalDosesToday > 0 
            ? round(($takenDosesToday / $totalDosesToday) * 100) 
            : null;

        // Today's task completion
        $todaysTasks = Checklist::where('elderly_id', $elderly->id)
            ->whereDate('due_date', $today)
            ->get();
        
        $totalTasks = $todaysTasks->count();
        $completedTasks = $todaysTasks->where('is_completed', true)->count();
        
        $taskCompletion = $totalTasks > 0 
            ? round(($completedTasks / $totalTasks) * 100) 
            : null;

        // Vitals recorded today
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
