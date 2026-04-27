<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        $intended = $request->session()->get('url.intended');

        if (is_string($intended)) {
            $intendedPath = parse_url($intended, PHP_URL_PATH) ?: '';

            // Avoid carrying over generic pages across account switches.
            if (in_array($intendedPath, ['/', '/profile', '/login'], true)) {
                $request->session()->forget('url.intended');
            }
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     * Routes users to correct dashboard based on user_type.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        return redirect()->to($this->resolvePostLoginRedirect($request, $user));
    }

    /**
     * Resolve a role-safe post-login destination.
     */
    protected function resolvePostLoginRedirect(Request $request, User $user): string
    {
        $fallback = $this->resolveRedirectForUser($user);
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended)) {
            return $fallback;
        }

        $intendedPath = parse_url($intended, PHP_URL_PATH);
        if (! is_string($intendedPath) || $intendedPath === '') {
            return $fallback;
        }

        $normalizedPath = '/' . ltrim($intendedPath, '/');
        if (! $this->isAllowedIntendedPath($user, $normalizedPath)) {
            return $fallback;
        }

        $intendedQuery = parse_url($intended, PHP_URL_QUERY);

        return is_string($intendedQuery) && $intendedQuery !== ''
            ? $normalizedPath . '?' . $intendedQuery
            : $normalizedPath;
    }

    /**
     * Allow intended redirects only when path is compatible with the user's role.
     */
    protected function isAllowedIntendedPath(User $user, string $path): bool
    {
        $blockedPrefixes = [
            '/login',
            '/register',
            '/forgot-password',
            '/reset-password',
            '/verify-email',
            '/email/verification-notification',
        ];

        foreach ($blockedPrefixes as $blockedPrefix) {
            if (str_starts_with($path, $blockedPrefix)) {
                return false;
            }
        }

        $profile = $user->profile;

        if (! $profile || ! $profile->hasKnownRole()) {
            return str_starts_with($path, '/auth/select-role');
        }

        if ($profile->isCaregiver()) {
            return str_starts_with($path, '/caregiver')
                || str_starts_with($path, '/profile')
                || str_starts_with($path, '/calendar');
        }

        return ! str_starts_with($path, '/caregiver');
    }

    /**
     * Resolve the post-login destination by role.
     */
    protected function resolveRedirectForUser(User $user): string
    {
        $profile = $user->profile;

        if (! $profile || ! $profile->hasKnownRole()) {
            return route('auth.select-role', absolute: false);
        }

        return $profile->isCaregiver()
            ? route('caregiver.dashboard', absolute: false)
            : route('dashboard', absolute: false);
    }

    /**
     * Destroy an authenticated session.
     * Properly invalidates session and clears all cookies to prevent back-button access.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Logout the user
        Auth::guard('web')->logout();

        // Invalidate the session completely
        $request->session()->invalidate();

        // Regenerate the CSRF token
        $request->session()->regenerateToken();

        // Clear the remember me cookie if it exists
        $cookie = Cookie::forget('remember_web_' . sha1(static::class));

        // Redirect with cache-control headers to prevent back button access
        return redirect('/')
            ->withCookie($cookie)
            ->withHeaders([
                'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => 'Sat, 01 Jan 2000 00:00:00 GMT',
            ]);
    }
}
