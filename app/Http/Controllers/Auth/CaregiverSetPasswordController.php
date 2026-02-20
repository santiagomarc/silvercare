<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules;

class CaregiverSetPasswordController extends Controller
{
    /**
     * Show the password setup form for new caregivers
     */
    public function show(Request $request, $userId)
    {
        // Verify the signed URL hasn't expired (valid for 7 days)
        if (!$request->hasValidSignature()) {
            abort(403, 'This invitation link has expired or is invalid.');
        }

        $user = User::findOrFail($userId);

        // Verify this is a caregiver who hasn't set a password yet
        if ($user->profile->user_type !== 'caregiver') {
            abort(403, 'Invalid invitation link.');
        }

        return view('auth.caregiver-set-password', [
            'user' => $user,
            'email' => $user->email,
        ]);
    }

    /**
     * Handle the password setup submission
     */
    public function store(Request $request, $userId)
    {
        // Verify the signed URL
        if (!$request->hasValidSignature()) {
            abort(403, 'This invitation link has expired or is invalid.');
        }

        $user = User::findOrFail($userId);

        // Validate password
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Update the caregiver's password
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Log the user in
        Auth::login($user);

        // Redirect to caregiver dashboard
        return redirect()->route('caregiver.dashboard')->with('success', 
            'Password set successfully! Welcome to SilverCare.'
        );
    }
}
