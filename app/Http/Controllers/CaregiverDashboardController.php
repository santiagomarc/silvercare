<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LinkCode;
use App\Models\HealthMetric;
use App\Models\MedicationLog;
use App\Models\Checklist;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CaregiverDashboardController extends Controller
{
    public function index(Request $request)
    {
        $caregiver = Auth::user()->profile;
        $activeLinkCode = null;
        $activeLinkQrSvg = null;
        $activeLinkSignedUrl = null;
        
        // Ensure the user has a profile
        if (!$caregiver) {
            return redirect()->route('profile.completion');
        }

        $elderlyPatients = $caregiver->elderlyPatients()->with('user')->orderBy('id')->get();
        $requestedElderlyId = $request->integer('elderly');
        $elderly = $requestedElderlyId
            ? $elderlyPatients->firstWhere('id', $requestedElderlyId)
            : null;
        $elderly = $elderly ?? $elderlyPatients->first();
        $selectedElderlyId = $elderly?->id;

        if ($caregiver) {
            $activeLinkCode = LinkCode::where('caregiver_profile_id', $caregiver->id)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->first();

            if ($activeLinkCode) {
                $activeLinkSignedUrl = URL::temporarySignedRoute(
                    'elderly.link',
                    $activeLinkCode->expires_at,
                    [
                        'code' => $activeLinkCode->code,
                        'caregiver' => $caregiver->id,
                    ]
                );

                $activeLinkQrSvg = (string) QrCode::format('svg')
                    ->size(200)
                    ->margin(1)
                    ->errorCorrection('M')
                    ->generate($activeLinkSignedUrl);
            }
        }

        if (!$elderly) {
            return view('caregiver.dashboard', [
                'elderly' => null,
                'elderlyPatients' => $elderlyPatients,
                'selectedElderlyId' => null,
                'elderlyUser' => null,
                'mood' => null,
                'vitals' => [],
                'recentActivity' => collect(),
                'stats' => [],
                'activeLinkCode' => $activeLinkCode,
                'activeLinkQrSvg' => $activeLinkQrSvg,
                'activeLinkSignedUrl' => $activeLinkSignedUrl,
            ]);
        }

        // Get the elderly user
        $elderlyUser = $elderly->user;

        // Fetch TODAY's latest metrics in a single query, grouped by type
        $today = Carbon::today();
        
        $mood = HealthMetric::where('elderly_id', $elderly->id)
            ->where('type', 'mood')
            ->whereDate('measured_at', $today)
            ->latest('measured_at')
            ->first();

        $todayMetrics = HealthMetric::where('elderly_id', $elderly->id)
            ->whereIn('type', ['heart_rate', 'blood_pressure', 'sugar_level', 'temperature'])
            ->whereDate('measured_at', $today)
            ->orderBy('measured_at', 'desc')
            ->get()
            ->unique('type')
            ->keyBy('type');

        $heartRate     = $todayMetrics->get('heart_rate');
        $bloodPressure = $todayMetrics->get('blood_pressure');
        $sugarLevel    = $todayMetrics->get('sugar_level');
        $temperature   = $todayMetrics->get('temperature');

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

        return view('caregiver.dashboard', compact('elderly', 'elderlyPatients', 'selectedElderlyId', 'elderlyUser', 'mood', 'vitals', 'recentActivity', 'stats', 'conditions', 'medications', 'allergies', 'activeLinkCode', 'activeLinkQrSvg', 'activeLinkSignedUrl'));
    }

    /**
     * Get recent activity for the elderly (including notifications)
     */
    private function getRecentActivity($elderlyId)
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $notifications = Notification::where('elderly_id', $elderlyId)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return \App\Presenters\NotificationPresenter::toCaregiverActivity($notifications)
            ->sortByDesc('timestamp')
            ->take(30)
            ->values();
    }

    /**
     * Get summary stats for the elderly
     */
    private function getStats($elderly)
    {
        $today = Carbon::today();
        $dayOfWeek = $today->format('l');

        // Today's medication adherence — bulk-loaded
        $todaysMeds = $elderly->trackedMedications()
            ->where('is_active', true)
            ->get();

        $applicableMeds = $todaysMeds->filter(fn ($med) => in_array($dayOfWeek, $med->days_of_week ?? []));
        $medIds = $applicableMeds->pluck('id');

        $totalDosesToday = $applicableMeds->sum(fn ($med) => count($med->times_of_day ?? []));

        // Single bulk query for all medication logs today
        $takenDosesToday = MedicationLog::whereIn('medication_id', $medIds)
            ->whereDate('scheduled_time', $today)
            ->where('is_taken', true)
            ->count();
        
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
