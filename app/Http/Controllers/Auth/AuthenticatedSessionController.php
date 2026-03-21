<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
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

        return redirect()->intended($this->resolveRedirectForUser($user));
    }

    /**
     * Resolve the post-login destination by role, creating a fallback profile when missing.
     */
    protected function resolveRedirectForUser(User $user): string
    {
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
