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

        if (!$profile || $profile->user_type !== 'elderly') {
            // Redirect caregivers to their dashboard
            if ($profile && $profile->user_type === 'caregiver') {
                return redirect()->route('caregiver.dashboard')
                    ->with('error', 'You do not have access to the elderly interface.');
            }
            
            // No profile - go to login
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Your account is not properly configured.');
        }

        return $next($request);
    }
}
