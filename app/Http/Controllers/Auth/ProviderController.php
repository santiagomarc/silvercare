<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class ProviderController extends Controller
{
    /**
     * Redirect users to Google OAuth.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in failed. Please try again.',
            ]);
        }

        $user = User::where('google_id', $googleUser->id)
            ->orWhere('email', $googleUser->email)
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->name ?: 'SilverCare User',
                'email' => $googleUser->email,
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
                'google_id' => $googleUser->id,
                'google_avatar' => $googleUser->avatar,
            ]);
        } else {
            $user->update([
                'google_id' => $user->google_id ?: $googleUser->id,
                'google_avatar' => $googleUser->avatar,
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $profile = $user->profile;
        if (! $profile) {
            return redirect()->route('auth.select-role');
        }

        return $profile->isCaregiver()
            ? redirect()->route('caregiver.dashboard')
            : redirect()->route('dashboard');
    }

    /**
     * Show role selection for first-time OAuth users.
     */
    public function showRoleSelection(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->profile) {
            return $user->profile->isCaregiver()
                ? redirect()->route('caregiver.dashboard')
                : redirect()->route('dashboard');
        }

        return view('auth.select-role');
    }

    /**
     * Persist selected role and create initial profile.
     */
    public function storeRoleSelection(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_type' => ['required', 'in:elderly,caregiver'],
        ]);

        $user = $request->user();

        if (! $user->profile) {
            UserProfile::create([
                'user_id' => $user->id,
                'user_type' => $validated['user_type'],
                'username' => Str::slug($user->name) ?: ('user-' . $user->id),
                'profile_completed' => $validated['user_type'] === 'caregiver',
                'is_active' => true,
            ]);
        }

        return $validated['user_type'] === 'caregiver'
            ? redirect()->route('caregiver.dashboard')
            : redirect()->route('dashboard');
    }
}
