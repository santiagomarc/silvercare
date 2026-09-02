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
     * H5 — caregiver places a dose on hold (patient is NPO, pre-op, unwell).
     *
     * Caregiver-only: holding is a clinical instruction, not something the
     * senior decides for themselves.
     */
    public function hold(Request $request, DoseInstance $doseInstance): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user = Auth::user();
        $profile = $user->profile;

        if (! $profile || ! $profile->isCaregiver() || $doseInstance->elderly?->caregiver_id !== $profile->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the linked caregiver can hold a dose.',
            ], 403);
        }

        $result = $this->doseService->holdDose($doseInstance, $user->id, $validated['reason']);

        return response()->json($result, $result['success'] ? 200 : 409);
    }

    /**
     * H5 — senior or caregiver skips a dose with a reason.
     *
     * Gives the senior an honest way to say "I'm not taking this" instead of
     * leaving the dose to silently rot into 'missed'.
     */
    public function skip(Request $request, DoseInstance $doseInstance): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user = Auth::user();
        $profile = $user->profile;

        if (! $profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        $isOwner = ($doseInstance->elderly_id === $profile->id);
        $isCaregiver = ($profile->isCaregiver() && $doseInstance->elderly?->caregiver_id === $profile->id);

        if (! $isOwner && ! $isCaregiver) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $result = $this->doseService->skipDose(
            $doseInstance,
            $isCaregiver ? 'caregiver' : 'senior_ui',
            $user->id,
            $validated['reason']
        );

        return response()->json($result, $result['success'] ? 200 : 409);
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
