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
                'status' => $this->getHeartRateStatus($heartRate->value),
            ] : null,
            'blood_pressure' => $bloodPressure ? [
                'metric' => $bloodPressure,
                'status' => $this->getBloodPressureStatus($bloodPressure->value_text),
            ] : null,
            'sugar_level' => $sugarLevel ? [
                'metric' => $sugarLevel,
                'status' => $this->getSugarLevelStatus($sugarLevel->value),
            ] : null,
            'temperature' => $temperature ? [
                'metric' => $temperature,
                'status' => $this->getTemperatureStatus($temperature->value),
            ] : null,
        ];

        // Get recent activity (last 7 days)
        $recentActivity = $this->getRecentActivity($elderly->id);

        // Get summary stats
        $stats = $this->getStats($elderly);

        return view('caregiver.dashboard', compact('elderly', 'elderlyUser', 'mood', 'vitals', 'recentActivity', 'stats'));
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

    /**
     * Health status helper methods (matching Flutter thresholds)
     */
    private function getHeartRateStatus($value)
    {
        if ($value >= 150) return ['label' => 'Critical', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-700'];
        if ($value >= 100) return ['label' => 'High', 'color' => 'orange', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
        if ($value < 50) return ['label' => 'Very Low', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-700'];
        if ($value < 60) return ['label' => 'Low', 'color' => 'blue', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
        return ['label' => 'Normal', 'color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
    }

    private function getBloodPressureStatus($value)
    {
        if (!$value || !str_contains($value, '/')) {
            return ['label' => 'Unknown', 'color' => 'gray', 'bg' => 'bg-gray-100', 'text' => 'text-gray-700'];
        }
        
        $parts = explode('/', $value);
        $systolic = (int)$parts[0];
        $diastolic = (int)($parts[1] ?? 0);

        if ($systolic >= 180 || $diastolic >= 120) return ['label' => 'Critical', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-700'];
        if ($systolic >= 140 || $diastolic >= 90) return ['label' => 'High', 'color' => 'orange', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
        if ($systolic >= 130 || $diastolic >= 80) return ['label' => 'Elevated', 'color' => 'yellow', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'];
        if ($systolic < 90 || $diastolic < 60) return ['label' => 'Low', 'color' => 'blue', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
        return ['label' => 'Normal', 'color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
    }

    private function getSugarLevelStatus($value)
    {
        if ($value >= 250) return ['label' => 'Critical', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-700'];
        if ($value >= 180) return ['label' => 'High', 'color' => 'orange', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
        if ($value >= 126) return ['label' => 'Elevated', 'color' => 'yellow', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'];
        if ($value < 70) return ['label' => 'Low', 'color' => 'blue', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
        return ['label' => 'Normal', 'color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
    }

    private function getTemperatureStatus($value)
    {
        if ($value >= 39.5) return ['label' => 'High Fever', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-700'];
        if ($value >= 38.0) return ['label' => 'Fever', 'color' => 'orange', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
        if ($value >= 37.3) return ['label' => 'Elevated', 'color' => 'yellow', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'];
        if ($value < 36.0) return ['label' => 'Low', 'color' => 'blue', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
        return ['label' => 'Normal', 'color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
    }
}
