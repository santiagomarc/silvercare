<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicationDoseRequest;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\Checklist;
use App\Models\HealthMetric;
use App\Services\ElderlyDashboardService;
use App\Services\MedicationWindowService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ElderlyDashboardController extends Controller
{
    public function __construct(
        protected ElderlyDashboardService $dashboardService,
        protected MedicationWindowService $windowService
    )
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
        $data['linkedCaregiver'] = $user->profile?->caregiver;

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
            'gardenStreakDays' => 0, 'gardenIsWilting' => false, 'gardenMissedCount' => 0,
            'todayMood' => 3, 'moodRecordedToday' => false, 'upcomingEvents' => [], 'unreadNotifications' => 0,
            'linkedCaregiver' => null,
        ];
    }

    public function medications()
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;
        $unreadNotifications = $elderlyId
            ? $this->unreadNotificationsCount($elderlyId)
            : 0;

        $medications = collect();
        $medicationLogs = collect();

        if ($elderlyId) {
            $medications = Medication::where('elderly_id', $elderlyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->with('schedules')
                ->get();

            // Get today's medication logs
            $medicationLogs = MedicationLog::where('elderly_id', $elderlyId)
                ->whereDate('scheduled_time', Carbon::today())
                ->get()
                ->keyBy(function ($log) {
                    return $log->medication_id . '_' . $log->scheduled_time->format('H:i');
                });
        }

        return view('elderly.medications', compact('medications', 'medicationLogs', 'unreadNotifications'));
    }

    public function checklists()
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;
        $unreadNotifications = $elderlyId
            ? $this->unreadNotificationsCount($elderlyId)
            : 0;

        $checklists = collect();
        if ($elderlyId) {
            $checklists = Checklist::where('elderly_id', $elderlyId)
                ->where(function ($query) {
                    $query->where('is_completed', false)
                        ->orWhereDate('due_date', '>=', Carbon::today()->subDays(7));
                })
                ->orderBy('is_completed')
                ->orderBy('due_date')
                ->orderBy('due_time')
                ->get();
        }

        // Group by date
        $groupedChecklists = $checklists->groupBy(function ($item) {
            return $item->due_date?->format('Y-m-d') ?? 'no-date';
        });

        return view('elderly.checklists', compact('checklists', 'groupedChecklists', 'unreadNotifications'));
    }

    /**
     * Toggle checklist completion status
     */
    public function toggleChecklist(Checklist $checklist)
    {
        $elderlyId = Auth::user()->profile?->id;
        $this->authorize('toggleCompletion', $checklist);

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
    public function takeMedication(MedicationDoseRequest $request, Medication $medication)
    {
        $elderlyId = Auth::user()->profile?->id;
        $this->authorize('take', $medication);

        $scheduledTime = $request->validated('time');
        $now = Carbon::now();
        $validTimes = $medication->scheduleTimesForDate(Carbon::today());

        if (!in_array($scheduledTime, $validTimes, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This medication is not scheduled at the selected time today.',
            ], 422);
        }

        $window = $this->windowService->forToday($scheduledTime, $now);
        $scheduledDateTime = $window['scheduled_time'];
        $windowStart = $window['window_start'];
        $windowEnd = $window['window_end'];
        $isWithinWindow = $window['is_within_window'];
        $isPastWindow = $window['is_past_window'];
        $isBeforeWindow = $window['is_before_window'];

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

        $existingLog = MedicationLog::where('elderly_id', $elderlyId)
            ->where('medication_id', $medication->id)
            ->where('scheduled_time', $scheduledDateTime)
            ->first();

        if ($existingLog?->is_taken) {
            return response()->json([
                'success' => true,
                'is_taken' => true,
                'taken_at' => $existingLog->taken_at?->toISOString(),
                'taken_late' => $existingLog->taken_at?->gt($windowEnd) ?? false,
                'status' => $existingLog->taken_at?->gt($windowEnd) ? 'taken_late' : 'taken',
                'message' => 'Medication already marked as taken.',
            ]);
        }

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

        if ($medication->track_inventory && $medication->current_stock > 0) {
            $medication->decrement('current_stock');
        }

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
    public function undoMedication(MedicationDoseRequest $request, Medication $medication)
    {
        $elderlyId = Auth::user()->profile?->id;
        $this->authorize('take', $medication);

        $scheduledTime = $request->validated('time');
        $now = Carbon::now();
        $validTimes = $medication->scheduleTimesForDate(Carbon::today());

        if (!in_array($scheduledTime, $validTimes, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This medication is not scheduled at the selected time today.',
            ], 422);
        }

        $window = $this->windowService->forToday($scheduledTime, $now);
        $scheduledDateTime = $window['scheduled_time'];
        $windowEnd = $window['window_end'];
        $isPastWindow = $window['is_past_window'];

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
            if ($medication->track_inventory && $log->is_taken) {
                $medication->increment('current_stock');
            }
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
        $window = app(MedicationWindowService::class)->forToday($scheduledTime);

        return [
            'can_take' => $window['can_take'],
            'is_within_window' => $window['is_within_window'],
            'is_past_window' => $window['is_past_window'],
            'is_before_window' => $window['is_before_window'],
            'is_late' => $window['is_past_window'],
            'window_start' => $window['window_start'],
            'window_end' => $window['window_end'],
            'scheduled_time' => $window['scheduled_time'],
        ];
    }

    private function unreadNotificationsCount(int $elderlyId): int
    {
        return \App\Models\Notification::where('elderly_id', $elderlyId)
            ->where('type', '!=', 'medication_refill_caregiver')
            ->where('is_read', false)
            ->count();
    }
}
