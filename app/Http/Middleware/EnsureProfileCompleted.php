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
     */
    protected array $exceptRouteNames = [
        'profile.completion',
        'profile.completion.store',
        'logout',
    ];

    /**
     * Handle an incoming request.
     *
     * If the authenticated user's profile is not yet completed,
     * redirect them to the completion wizard.
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
            return $next($request);
        }

        // Redirect to profile completion if not complete
        if (!$profile->profile_completed) {
            return redirect()->route('profile.completion')
                ->with('info', 'Please complete your profile before continuing.');
        }

        return $next($request);
    }
}
