<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    /**
     * List alerts for caregiver's linked patients.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile || !$profile->isCaregiver()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $patientIds = $profile->linkedElderly()->pluck('id');

        $query = Alert::whereIn('elderly_id', $patientIds)
            ->with(['elderly.user', 'deliveries']);

        $status = $request->query('status', 'open');
        if ($status !== 'all') {
            $query->where('state', $status);
        }

        $alerts = $query->orderByRaw("
            CASE severity
                WHEN 'emergency' THEN 1
                WHEN 'critical' THEN 2
                WHEN 'warning' THEN 3
                ELSE 4
            END
        ")->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'alerts' => $alerts,
        ]);
    }

    /**
     * Caregiver acknowledges an alert.
     */
    public function acknowledge(Request $request, Alert $alert): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile || !$profile->isCaregiver()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        // Verify patient is linked to this caregiver
        if ($alert->elderly?->caregiver_id !== $profile->id) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this patient\'s alerts.'], 403);
        }

        $alert->acknowledge($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged successfully.',
            'alert' => $alert->fresh(['acknowledgedBy', 'resolvedBy']),
        ]);
    }

    /**
     * Caregiver resolves an alert.
     */
    public function resolve(Request $request, Alert $alert): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile || !$profile->isCaregiver()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        // Verify patient is linked to this caregiver
        if ($alert->elderly?->caregiver_id !== $profile->id) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this patient\'s alerts.'], 403);
        }

        $alert->resolve($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Alert resolved successfully.',
            'alert' => $alert->fresh(['acknowledgedBy', 'resolvedBy']),
        ]);
    }
}
