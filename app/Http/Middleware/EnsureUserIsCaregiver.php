<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCaregiver
{
    /**
     * Handle an incoming request.
     * Ensures the authenticated user is a caregiver type.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        $profile = $user->profile;

        if (!$profile || $profile->user_type !== 'caregiver') {
            // Redirect elderly to their dashboard
            if ($profile && $profile->user_type === 'elderly') {
                return redirect()->route('dashboard')
                    ->with('error', 'You do not have access to the caregiver interface.');
            }
            
            // No profile - go to login
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Your account is not properly configured.');
        }

        return $next($request);
    }
}
