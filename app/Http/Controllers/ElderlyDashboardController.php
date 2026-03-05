<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\Checklist;
use App\Models\HealthMetric;
use App\Models\GoogleFitToken;
use App\Services\ElderlyDashboardService;
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

    public function __construct(protected ElderlyDashboardService $dashboardService)
    {
    }

    public function index()
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        if (!$elderlyId) {
            return view('elderly.dashboard', $this->emptyDashboard());
        }

        $data = $this->dashboardService->getDashboardData($elderlyId, $user->id);

        return view('elderly.dashboard', $data);
    }

    private function emptyDashboard(): array
    {
        return [
            'medications' => collect(), 'todayMedications' => collect(), 'medicationLogs' => collect(),
            'checklists' => collect(), 'todayChecklists' => collect(),
            'completedChecklists' => 0, 'totalChecklists' => 0, 'checklistProgress' => 0,
            'todayVitals' => collect(), 'recordedVitalTypes' => [], 'completedVitals' => 0,
            'totalRequiredVitals' => 4, 'vitalsProgress' => 0, 'vitalsData' => [],
            'stepsData' => null, 'takenMedicationDoses' => 0, 'totalMedicationDoses' => 0,
            'medicationProgress' => 0, 'dailyGoalsProgress' => 0, 'googleFitConnected' => false,
            'todayMood' => 3, 'upcomingEvents' => [], 'unreadNotifications' => 0,
        ];
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
