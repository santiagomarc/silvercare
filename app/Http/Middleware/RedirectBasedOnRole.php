<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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

            if (! $profile) {
                $profile = UserProfile::create([
                    'user_id' => $user->id,
                    'user_type' => 'elderly',
                    'username' => Str::slug($user->name) ?: ('user-' . $user->id),
                    'profile_completed' => false,
                    'is_active' => true,
                ]);
            }

            if ($profile) {
                if ($profile->user_type === 'elderly') {
                    return redirect()->route('dashboard');
                } elseif ($profile->user_type === 'caregiver') {
                    return redirect()->route('caregiver.dashboard');
                }
            }
        }

        return $next($request);
    }
}
