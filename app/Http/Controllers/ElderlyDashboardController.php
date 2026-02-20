<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\Checklist;
use App\Models\HealthMetric;
use App\Models\GoogleFitToken;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ElderlyDashboardController extends Controller
{
    /**
     * Grace period in minutes for taking medication (1 hour before and after scheduled time)
     */
    const MEDICATION_GRACE_MINUTES = 60;

    /**
     * Required vital types for daily goals tracking
     */
    const REQUIRED_VITALS = ['heart_rate', 'blood_pressure', 'sugar_level', 'temperature'];

    public function index()
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        // Get medications assigned to this elderly
        $medications = collect();
        $todayMedications = collect();
        $medicationLogs = collect();
        
        if ($elderlyId) {
            $medications = Medication::where('elderly_id', $elderlyId)
                ->where('is_active', true)
                ->get();
            
            // Filter today's medications based on days_of_week
            $todayName = Carbon::now()->format('l'); // e.g., "Monday"
            $todayMedications = $medications->filter(function ($med) use ($todayName) {
                return empty($med->days_of_week) || in_array($todayName, $med->days_of_week);
            });

            // Get today's medication logs for this elderly
            $medicationLogs = MedicationLog::where('elderly_id', $elderlyId)
                ->whereDate('scheduled_time', Carbon::today())
                ->get()
                ->keyBy(function ($log) {
                    // Create key: medication_id_HH:mm
                    return $log->medication_id . '_' . $log->scheduled_time->format('H:i');
                });
        }

        // Get checklists for today
        $checklists = collect();
        $todayChecklists = collect();
        
        if ($elderlyId) {
            $checklists = Checklist::where('elderly_id', $elderlyId)->get();
            
            $todayChecklists = Checklist::where('elderly_id', $elderlyId)
                ->whereDate('due_date', Carbon::today())
                ->orderBy('due_time')
                ->get();
        }

        // Get today's vitals - check which vitals have been recorded today
        $todayVitals = collect();
        $recordedVitalTypes = [];
        
        if ($elderlyId) {
            $todayVitals = HealthMetric::where('elderly_id', $elderlyId)
                ->whereDate('measured_at', Carbon::today())
                ->get();
            
            // Get unique vital types recorded today
            $recordedVitalTypes = $todayVitals->pluck('type')->unique()->toArray();
        }

        // Calculate vitals progress (based on required daily vitals)
        $totalRequiredVitals = count(self::REQUIRED_VITALS);
        $completedVitals = count(array_intersect(self::REQUIRED_VITALS, $recordedVitalTypes));
        $vitalsProgress = $totalRequiredVitals > 0 ? round(($completedVitals / $totalRequiredVitals) * 100) : 0;

        // Calculate checklist progress
        $completedChecklists = $todayChecklists->where('is_completed', true)->count();
        $totalChecklists = $todayChecklists->count();
        $checklistProgress = $totalChecklists > 0 ? round(($completedChecklists / $totalChecklists) * 100) : 0;

        // Calculate medication progress
        $totalMedicationDoses = 0;
        $takenMedicationDoses = 0;
        foreach ($todayMedications as $med) {
            $times = $med->times_of_day ?? [];
            $totalMedicationDoses += count($times);
            foreach ($times as $time) {
                $logKey = $med->id . '_' . $time;
                if (isset($medicationLogs[$logKey]) && $medicationLogs[$logKey]->is_taken) {
                    $takenMedicationDoses++;
                }
            }
        }
        $medicationProgress = $totalMedicationDoses > 0 ? round(($takenMedicationDoses / $totalMedicationDoses) * 100) : 0;

        // Calculate overall daily goals progress (weighted average)
        // Weights: Checklists 40%, Medications 40%, Vitals 20%
        $hasChecklists = $totalChecklists > 0;
        $hasMedications = $totalMedicationDoses > 0;
        $hasVitals = $totalRequiredVitals > 0;

        // Calculate dynamic weights based on what's applicable
        $totalWeight = 0;
        $weightedProgress = 0;

        if ($hasChecklists) {
            $totalWeight += 40;
            $weightedProgress += $checklistProgress * 40;
        }
        if ($hasMedications) {
            $totalWeight += 40;
            $weightedProgress += $medicationProgress * 40;
        }
        if ($hasVitals) {
            $totalWeight += 20;
            $weightedProgress += $vitalsProgress * 20;
        }

        $dailyGoalsProgress = $totalWeight > 0 ? round($weightedProgress / $totalWeight) : 0;

        // Check Google Fit connection status
        $googleFitConnected = GoogleFitToken::where('user_id', $user->id)->exists();

        // Organize vitals data for display - keyed by type with latest values
        $vitalsData = [];
        foreach (self::REQUIRED_VITALS as $vitalType) {
            $latestMetric = $todayVitals->where('type', $vitalType)->sortByDesc('measured_at')->first();
            $vitalsData[$vitalType] = [
                'recorded' => $latestMetric !== null,
                'value' => $latestMetric?->value,
                'value_text' => $latestMetric?->value_text,
                'unit' => $latestMetric?->unit,
                'measured_at' => $latestMetric?->measured_at,
                'source' => $latestMetric?->source,
            ];
        }

        // Get today's steps from Google Fit (if connected)
        $stepsData = null;
        if ($elderlyId) {
            $stepsMetric = HealthMetric::where('elderly_id', $elderlyId)
                ->where('type', 'steps')
                ->whereDate('measured_at', today())
                ->orderBy('measured_at', 'desc')
                ->first();
            
            if ($stepsMetric) {
                $stepsData = [
                    'value' => (int) $stepsMetric->value,
                    'goal' => 6000, // Default daily step goal for seniors
                    'source' => $stepsMetric->source,
                    'synced_at' => $stepsMetric->measured_at,
                ];
            }
        }

        // Get today's mood
        $todayMood = 3; // Default to neutral
        if ($elderlyId) {
            $moodMetric = HealthMetric::where('elderly_id', $elderlyId)
                ->where('type', 'mood')
                ->whereDate('measured_at', Carbon::today())
                ->first();
            if ($moodMetric) {
                $todayMood = (int) $moodMetric->value;
            }
        }

        return view('elderly.dashboard', compact(
            'medications',
            'todayMedications',
            'medicationLogs',
            'checklists',
            'todayChecklists',
            'completedChecklists',
            'totalChecklists',
            'checklistProgress',
            'todayVitals',
            'recordedVitalTypes',
            'completedVitals',
            'totalRequiredVitals',
            'vitalsProgress',
            'vitalsData',
            'stepsData',
            'takenMedicationDoses',
            'totalMedicationDoses',
            'medicationProgress',
            'dailyGoalsProgress',
            'googleFitConnected',
            'todayMood'
        ));
    }

    public function medications()
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        $medications = collect();
        $medicationLogs = collect();

        if ($elderlyId) {
            $medications = Medication::where('elderly_id', $elderlyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            // Get today's medication logs
            $medicationLogs = MedicationLog::where('elderly_id', $elderlyId)
                ->whereDate('scheduled_time', Carbon::today())
                ->get()
                ->keyBy(function ($log) {
                    return $log->medication_id . '_' . $log->scheduled_time->format('H:i');
                });
        }

        return view('elderly.medications', compact('medications', 'medicationLogs'));
    }

    public function checklists()
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        $checklists = collect();
        if ($elderlyId) {
            $checklists = Checklist::where('elderly_id', $elderlyId)
                ->whereDate('due_date', '>=', Carbon::today()->subDays(7))
                ->orderBy('due_date')
                ->orderBy('due_time')
                ->get();
        }

        // Group by date
        $groupedChecklists = $checklists->groupBy(function ($item) {
            return $item->due_date->format('Y-m-d');
        });

        return view('elderly.checklists', compact('checklists', 'groupedChecklists'));
    }

    /**
     * Toggle checklist completion status
     */
    public function toggleChecklist(Checklist $checklist)
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        // Ensure the checklist belongs to this elderly
        if ($checklist->elderly_id !== $elderlyId) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            abort(403);
        }

        $newStatus = !$checklist->is_completed;
        
        $checklist->update([
            'is_completed' => $newStatus,
            'completed_at' => $newStatus ? now() : null,
        ]);

        // Create notification if task was completed
        if ($newStatus) {
            app(NotificationService::class)->createTaskCompletedNotification(
                $elderlyId,
                $checklist->task,
                $checklist->category ?? 'General'
            );
        }

        // Return JSON for AJAX requests
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'is_completed' => $checklist->is_completed,
                'completed_at' => $checklist->completed_at?->toISOString(),
                'message' => $checklist->is_completed ? 'Task completed!' : 'Task marked as incomplete'
            ]);
        }

        return back()->with('success', $checklist->is_completed ? 'Task completed!' : 'Task marked as incomplete');
    }

    /**
     * Mark a medication dose as taken
     */
    public function takeMedication(Request $request, Medication $medication)
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        // Ensure the medication belongs to this elderly
        if ($medication->elderly_id !== $elderlyId) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            abort(403);
        }

        $request->validate([
            'time' => 'required|string', // Format: HH:mm
        ]);

        $scheduledTime = $request->input('time');
        $now = Carbon::now();
        $today = Carbon::today();

        // Create the scheduled datetime for today
        $scheduledDateTime = Carbon::parse($today->format('Y-m-d') . ' ' . $scheduledTime);

        // Check if within the 1-hour grace period (1 hour before to 1 hour after)
        $windowStart = $scheduledDateTime->copy()->subMinutes(self::MEDICATION_GRACE_MINUTES);
        $windowEnd = $scheduledDateTime->copy()->addMinutes(self::MEDICATION_GRACE_MINUTES);

        $isWithinWindow = $now->between($windowStart, $windowEnd);
        $isPastWindow = $now->gt($windowEnd);
        $isBeforeWindow = $now->lt($windowStart);

        // Determine status
        $status = 'pending';
        $takenLate = false;

        if ($isWithinWindow) {
            $status = 'taken';
        } elseif ($isPastWindow) {
            $status = 'taken_late';
            $takenLate = true;
        } else {
            // Before window - cannot take yet
            return response()->json([
                'success' => false,
                'message' => 'Too early to take this medication. Please wait until ' . $windowStart->format('g:i A'),
                'can_take' => false,
                'window_start' => $windowStart->toISOString(),
            ], 400);
        }

        // Create or update the medication log
        $logKey = $medication->id . '_' . $scheduledTime;
        
        $log = MedicationLog::updateOrCreate(
            [
                'elderly_id' => $elderlyId,
                'medication_id' => $medication->id,
                'scheduled_time' => $scheduledDateTime,
            ],
            [
                'is_taken' => true,
                'taken_at' => $now,
            ]
        );

        // Create notification for medication taken (with late flag)
        app(NotificationService::class)->createMedicationTakenNotification(
            $elderlyId,
            $medication->name,
            $takenLate
        );

        return response()->json([
            'success' => true,
            'is_taken' => true,
            'taken_at' => $log->taken_at->toISOString(),
            'taken_late' => $takenLate,
            'status' => $status,
            'message' => $takenLate ? 'Medication marked as taken (late)' : 'Medication taken!',
        ]);
    }

    /**
     * Undo a medication dose
     */
    public function undoMedication(Request $request, Medication $medication)
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        // Ensure the medication belongs to this elderly
        if ($medication->elderly_id !== $elderlyId) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            abort(403);
        }

        $request->validate([
            'time' => 'required|string', // Format: HH:mm
        ]);

        $scheduledTime = $request->input('time');
        $now = Carbon::now();
        $today = Carbon::today();
        $scheduledDateTime = Carbon::parse($today->format('Y-m-d') . ' ' . $scheduledTime);

        // Check if we're still within the grace period window
        $windowEnd = $scheduledDateTime->copy()->addMinutes(self::MEDICATION_GRACE_MINUTES);
        $isPastWindow = $now->gt($windowEnd);

        // Find the existing log
        $log = MedicationLog::where('elderly_id', $elderlyId)
            ->where('medication_id', $medication->id)
            ->where('scheduled_time', $scheduledDateTime)
            ->first();

        // If past grace period, don't allow unmarking
        if ($isPastWindow) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot unmark - grace period has ended',
                'can_undo' => false,
            ], 400);
        }

        // Delete the log if it exists
        if ($log) {
            $log->delete();
        }

        return response()->json([
            'success' => true,
            'is_taken' => false,
            'message' => 'Medication unmarked',
        ]);
    }

    /**
     * Helper: Check if a dose can be taken now
     */
    public static function canTakeDose(string $scheduledTime): array
    {
        $now = Carbon::now();
        $today = Carbon::today();
        $scheduledDateTime = Carbon::parse($today->format('Y-m-d') . ' ' . $scheduledTime);

        $windowStart = $scheduledDateTime->copy()->subMinutes(self::MEDICATION_GRACE_MINUTES);
        $windowEnd = $scheduledDateTime->copy()->addMinutes(self::MEDICATION_GRACE_MINUTES);

        $isWithinWindow = $now->between($windowStart, $windowEnd);
        $isPastWindow = $now->gt($windowEnd);
        $isBeforeWindow = $now->lt($windowStart);

        return [
            'can_take' => $isWithinWindow || $isPastWindow,
            'is_within_window' => $isWithinWindow,
            'is_past_window' => $isPastWindow,
            'is_before_window' => $isBeforeWindow,
            'is_late' => $isPastWindow,
            'window_start' => $windowStart,
            'window_end' => $windowEnd,
            'scheduled_time' => $scheduledDateTime,
        ];
    }
}
