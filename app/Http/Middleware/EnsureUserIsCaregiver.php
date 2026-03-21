<?php

namespace App\Http\Middleware;

use App\Models\UserProfile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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

        if (! $profile) {
            $profile = UserProfile::create([
                'user_id' => $user->id,
                'user_type' => 'caregiver',
                'username' => Str::slug($user->name) ?: ('user-' . $user->id),
                'profile_completed' => true,
                'is_active' => true,
            ]);
        }

        if (!$profile || $profile->user_type !== 'caregiver') {
            // Redirect elderly to their dashboard
            if ($profile && $profile->user_type === 'elderly') {
                return redirect()->route('dashboard')
                    ->with('error', 'You do not have access to the caregiver interface.');
            }

            return redirect()->route('welcome')
                ->with('error', 'Account role is not configured for caregiver access.');
        }

        return $next($request);
    }
}
