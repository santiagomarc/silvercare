<?php

namespace App\Http\Controllers;

use App\Models\DoseInstance;
use App\Services\DoseAdministrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoseInstanceController extends Controller
{
    public function __construct(
        protected DoseAdministrationService $doseService
    ) {
    }

    /**
     * Confirm a dose instance as taken with idempotency support.
     */
    public function confirm(Request $request, DoseInstance $doseInstance): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        // Must be the senior or their linked caregiver
        $isOwner = ($doseInstance->elderly_id === $profile->id);
        $isCaregiver = ($profile->isCaregiver() && $doseInstance->elderly?->caregiver_id === $profile->id);

        if (!$isOwner && !$isCaregiver) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $idempotencyKey = $request->header('X-Idempotency-Key') ?? $request->input('idempotency_key');
        $source = $isCaregiver ? 'caregiver' : 'senior_ui';

        $result = $this->doseService->confirmDose(
            $doseInstance,
            $source,
            $user->id,
            $idempotencyKey
        );

        if (!$result['success']) {
            $status = match ($result['error_code'] ?? '') {
                'DOSE_TOO_EARLY' => 400,
                'MEDICATION_EXPIRED', 'MEDICATION_NOT_STARTED' => 422,
                default => 400,
            };

            return response()->json($result, $status);
        }

        return response()->json($result);
    }

    /**
     * Undo a dose instance.
     */
    public function undo(Request $request, DoseInstance $doseInstance): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        $isOwner = ($doseInstance->elderly_id === $profile->id);
        $isCaregiver = ($profile->isCaregiver() && $doseInstance->elderly?->caregiver_id === $profile->id);

        if (!$isOwner && !$isCaregiver) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $source = $isCaregiver ? 'caregiver' : 'senior_ui';

        $result = $this->doseService->undoDose($doseInstance, $source, $user->id);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }
}
