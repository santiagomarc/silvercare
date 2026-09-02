<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * H8 — browser push subscription management.
 *
 * The browser subscribes with the VAPID public key and posts the resulting
 * endpoint plus encryption keys here. Nothing sensitive is stored: the endpoint
 * is an opaque push-service URL and the keys only encrypt payloads to that one
 * browser.
 */
class PushSubscriptionController extends Controller
{
    /**
     * The public key the browser needs in order to subscribe, plus whether push
     * is configured at all. The frontend skips the whole flow when it is not.
     */
    public function config(): JsonResponse
    {
        $publicKey = config('webpush.vapid.public_key');

        return response()->json([
            'enabled' => ! empty($publicKey) && ! empty(config('webpush.vapid.private_key')),
            'public_key' => $publicKey,
        ]);
    }

    /**
     * Register or refresh a subscription for the signed-in user's profile.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ]);

        $profile = Auth::user()?->profile;

        if (! $profile) {
            return response()->json([
                'success' => false,
                'message' => 'Complete your profile before enabling notifications.',
            ], 422);
        }

        $endpoint = $validated['endpoint'];

        // Re-subscribing from the same browser updates the existing row rather
        // than accumulating duplicates that would each fire a notification.
        $subscription = PushSubscription::updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashEndpoint($endpoint)],
            [
                'profile_id' => $profile->id,
                'endpoint' => $endpoint,
                'p256dh_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Push notifications enabled on this device.',
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Unsubscribe this browser. Called when the user turns notifications off or
     * the browser rotates the subscription.
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
        ]);

        $profile = Auth::user()?->profile;

        if (! $profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found.'], 422);
        }

        // Scoped to the caller's own profile so one user cannot unsubscribe
        // another's device by guessing an endpoint.
        $deleted = PushSubscription::where('profile_id', $profile->id)
            ->where('endpoint_hash', PushSubscription::hashEndpoint($validated['endpoint']))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted
                ? 'Push notifications disabled on this device.'
                : 'No matching subscription for this device.',
        ]);
    }
}
