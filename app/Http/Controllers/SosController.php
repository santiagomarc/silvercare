<?php

namespace App\Http\Controllers;

use App\Services\ClinicalRulesService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SosController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected ClinicalRulesService $rulesService,
    ) {}

    /**
     * Trigger an SOS alert to the linked caregiver.
     */
    public function trigger(Request $request)
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile || !$profile->isElderly()) {
            return response()->json([
                'success' => false,
                'message' => 'Only elderly users can trigger SOS.',
            ], 403);
        }

        $caregiverId = $profile->caregiver_id;

        if (!$caregiverId) {
            return response()->json([
                'success' => false,
                'message' => 'No caregiver linked. Please link a caregiver first.',
                'emergency_notice' => config('alerts.emergency_disclaimer'),
            ], 422);
        }

        try {
            $alert = $this->rulesService->evaluateSos($profile, $request->input('notes'));

            Log::warning("SOS triggered by user {$user->id} ({$user->name}), Alert #{$alert->id}");

            return response()->json([
                'success' => true,
                'message' => 'SOS alert sent to your caregiver!',
                'alert_id' => $alert->id,
                'emergency_notice' => config('alerts.emergency_disclaimer'),
            ]);

        } catch (\Exception $e) {
            Log::error('SOS trigger failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try calling your caregiver or emergency services directly.',
                'emergency_notice' => config('alerts.emergency_disclaimer'),
            ], 500);
        }
    }
}
