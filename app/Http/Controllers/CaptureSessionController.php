<?php

namespace App\Http\Controllers;

use App\Models\CaptureSession;
use App\Services\PrescriptionCaptureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CaptureSessionController extends Controller
{
    public function __construct(
        protected PrescriptionCaptureService $captureService,
    ) {
    }

    /**
     * Upload an image for multimodal extraction (prescription or vital screen photo).
     */
    public function upload(Request $request): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found.'], 404);
        }

        $validated = $request->validate([
            'image' => 'required|image|max:10240', // max 10MB
            'session_type' => 'required|string|in:prescription_scan,vital_photo_ocr',
            'elderly_id' => 'nullable|integer',
        ]);

        // If caregiver is uploading on behalf of elderly
        $targetElderly = $profile->isElderly()
            ? $profile
            : ($profile->linkedElderly()->where('id', $request->integer('elderly_id'))->first() ?? $profile->elderlyPatients->first());

        if (!$targetElderly) {
            return response()->json(['success' => false, 'message' => 'No patient targeted for capture.'], 422);
        }

        $session = $this->captureService->createSession($targetElderly, $validated['session_type'], $request->file('image'));

        return response()->json([
            'success' => true,
            'session' => $session,
        ]);
    }

    /**
     * View a capture session.
     */
    public function show(CaptureSession $session): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'success' => true,
            'session' => $session,
        ]);
    }

    /**
     * Confirm candidate data from capture session.
     */
    public function confirm(Request $request, CaptureSession $session): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'confirmed_data' => 'required|array',
        ]);

        $result = $this->captureService->confirmSession($session, $validated['confirmed_data'], $user);

        return response()->json($result);
    }
}
