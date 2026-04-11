<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SosController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
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
            ], 422);
        }

        $caregiver = $profile->caregiver;

        try {
            // Create an urgent in-app notification
            $this->notificationService->createNotification([
                'elderly_id' => $profile->id,
                'type' => 'sos_alert',
                'title' => '🚨 SOS Alert from ' . $user->name,
                'message' => $user->name . ' has triggered an emergency SOS alert and may need immediate assistance.',
                'severity' => 'warning',
                'metadata' => [
                    'source' => 'sos_button',
                    'timestamp' => now()->toIso8601String(),
                    'user_name' => $user->name,
                ],
            ]);

            // Also send email to caregiver if available
            if ($caregiver?->user?->email) {
                try {
                    Mail::raw(
                        "🚨 URGENT: {$user->name} has triggered an emergency SOS alert on SilverCare.\n\n" .
                        "Time: " . now()->format('l, F j, Y g:i A') . "\n\n" .
                        "Please check on them immediately.\n\n" .
                        "— SilverCare",
                        function ($message) use ($caregiver, $user) {
                            $message->to($caregiver->user->email)
                                ->subject("🚨 SOS Alert: {$user->name} needs help!");
                        }
                    );
                } catch (\Exception $e) {
                    Log::warning('SOS email failed: ' . $e->getMessage());
                    // Don't fail the whole request if email fails
                }
            }

            Log::warning("SOS triggered by user {$user->id} ({$user->name})");

            return response()->json([
                'success' => true,
                'message' => 'SOS alert sent to your caregiver!',
            ]);

        } catch (\Exception $e) {
            Log::error('SOS trigger failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try calling your caregiver directly.',
            ], 500);
        }
    }
}
