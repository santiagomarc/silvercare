<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    /**
     * Routes that bypass the profile-completion gate.
     * Includes profile completion flow and logout.
     */
    protected array $exceptRouteNames = [
        'profile.completion',
        'profile.completion.store',
        'profile.completion.skip',
        'logout',
    ];

    /**
     * Handle an incoming request.
     *
     * If the authenticated user's profile is not yet completed AND they have
     * not explicitly skipped onboarding, redirect them to the wizard so
     * their dashboard always has meaningful data.
     *
     * The `profile_skipped` flag allows users who tapped "Skip for now" to
     * reach the dashboard. A nudge banner there will encourage them to finish.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Let the request pass through for excluded routes.
        if ($request->routeIs($this->exceptRouteNames)) {
            return $next($request);
        }

        $profile = $user->profile;

        if (!$profile) {
            // No profile at all — let the role middleware handle it
            return $next($request);
        }

        // Redirect to the onboarding wizard when:
        //   • profile_completed is false, AND
        //   • the user has not explicitly chosen to skip (profile_skipped is false)
        if (!$profile->profile_completed && !$profile->profile_skipped) {
            return redirect()->route('profile.completion')
                ->with('info', 'Please complete your profile before continuing.');
        }

        return $next($request);
    }
}
