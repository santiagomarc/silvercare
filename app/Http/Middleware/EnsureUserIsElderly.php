<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsElderly
{
    /**
     * Handle an incoming request.
     * Ensures the authenticated user is an elderly type.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $profile = $user->profile;

        // No profile at all — send them to role selection rather than
        // silently creating a potentially-wrong profile.
        if (!$profile) {
            return redirect()->route('auth.select-role')
                ->with('info', 'Please select your account type to continue.');
        }

        if (! $profile->hasKnownRole()) {
            return redirect()->route('auth.select-role')
                ->with('error', 'Account role is invalid. Please select your account type.');
        }

        if (! $profile->isElderly()) {
            // Redirect caregivers to their dashboard
            if ($profile->isCaregiver()) {
                return redirect()->route('caregiver.dashboard')
                    ->with('error', 'You do not have access to the elderly interface.');
            }

            return redirect()->route('welcome')
                ->with('error', 'Account role is not configured for elderly access.');
        }

        return $next($request);
    }
}

