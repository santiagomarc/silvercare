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

            if ($profile) {
                if ($profile->user_type === 'elderly') {
                    // Check if profile is completed
                    if (!$profile->profile_completed) {
                        return redirect()->route('profile.completion');
                    }
                    return redirect()->route('dashboard');
                } elseif ($profile->user_type === 'caregiver') {
                    return redirect()->route('caregiver.dashboard');
                }
            }
        }

        return $next($request);
    }
}
