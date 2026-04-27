<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectBasedOnRole
{
    /**
     * Handle an incoming request.
     * Redirects authenticated users to their correct dashboard based on role.
     * Used for routes that should redirect logged-in users (like welcome page).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $profile = $user->profile;

            // No profile? Send them to role selection — do NOT silently create
            // one, as that could accidentally assign the wrong role.
            if (!$profile) {
                return redirect()->route('auth.select-role')
                    ->with('info', 'Please select your account type to continue.');
            }

            if (! $profile->hasKnownRole()) {
                return redirect()->route('auth.select-role')
                    ->with('error', 'Account role is invalid. Please select your account type.');
            }

            if ($profile->isElderly()) {
                return redirect()->route('dashboard');
            } elseif ($profile->isCaregiver()) {
                return redirect()->route('caregiver.dashboard');
            }
        }

        return $next($request);
    }
}
