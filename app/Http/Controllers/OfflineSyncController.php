<?php

namespace App\Http\Controllers;

use App\Models\OfflineSyncLog;
use App\Services\OfflineReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfflineSyncController extends Controller
{
    public function __construct(
        protected OfflineReconciliationService $reconciliationService,
    ) {
    }

    /**
     * Reconcile a batch of offline client mutations.
     */
    public function sync(Request $request): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found.'], 404);
        }

        $validated = $request->validate([
            'mutations' => 'required|array|min:1',
            'mutations.*.client_mutation_id' => 'required|string|max:64',
            'mutations.*.action_type' => 'required|string|in:confirm_dose,undo_dose,record_vital,daily_checkin',
            'mutations.*.payload' => 'required|array',
            'elderly_id' => 'nullable|integer',
        ]);

        $targetElderly = $profile->isElderly()
            ? $profile
            : ($profile->linkedElderly()->where('id', $request->integer('elderly_id'))->first() ?? $profile->elderlyPatients->first());

        if (!$targetElderly) {
            return response()->json(['success' => false, 'message' => 'No patient targeted for offline sync.'], 422);
        }

        $result = $this->reconciliationService->syncBatch($targetElderly, $validated['mutations'], $user);

        return response()->json($result);
    }

    /**
     * Get recent offline sync status and log history for the patient.
     */
    public function status(Request $request): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $targetElderlyId = $profile->isElderly()
            ? $profile->id
            : $request->integer('elderly_id', $profile->elderlyPatients->first()?->id ?? 0);

        $recentLogs = OfflineSyncLog::where('elderly_id', $targetElderlyId)
            ->latest('id')
            ->limit(20)
            ->get();

        $totalApplied = OfflineSyncLog::where('elderly_id', $targetElderlyId)->applied()->count();
        $totalConflicts = OfflineSyncLog::where('elderly_id', $targetElderlyId)->conflicts()->count();

        return response()->json([
            'success' => true,
            'elderly_id' => $targetElderlyId,
            'total_applied' => $totalApplied,
            'total_conflicts' => $totalConflicts,
            'recent_logs' => $recentLogs,
        ]);
    }
}
