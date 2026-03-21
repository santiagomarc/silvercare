<?php

namespace App\Http\Middleware;

use App\Models\UserProfile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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

        if (! $profile) {
            $profile = UserProfile::create([
                'user_id' => $user->id,
                'user_type' => 'elderly',
                'username' => Str::slug($user->name) ?: ('user-' . $user->id),
                'profile_completed' => false,
                'is_active' => true,
            ]);
        }

        if (!$profile || $profile->user_type !== 'elderly') {
            // Redirect caregivers to their dashboard
            if ($profile && $profile->user_type === 'caregiver') {
                return redirect()->route('caregiver.dashboard')
                    ->with('error', 'You do not have access to the elderly interface.');
            }

            return redirect()->route('welcome')
                ->with('error', 'Account role is not configured for elderly access.');
        }

        return $next($request);
    }
}
