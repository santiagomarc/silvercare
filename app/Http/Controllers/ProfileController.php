<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use App\Models\User;
use App\Services\ProfileCompletionService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileCompletionService $profileCompletionService,
    ) {
    }

    /**
     * Display the user's profile form.
     */
    public function edit()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Direct Database Query - Include caregiver relationship for emergency contact fallback
        $profile = UserProfile::with(['caregiver.user'])->where('user_id', $user->id)->first();

        // If no profile, create a blank instance so the view doesn't crash
        if (!$profile) {
            $profile = new UserProfile();
        }

        return view('profile.edit', compact('user', 'profile'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Validate
        $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'age'    => 'nullable|integer',
            'height' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
        ]);

        // 2. Update User Table (Name/Email)
       
        $nextEmail = $request->email ?? $user->email;
        $emailChanged = $nextEmail !== $user->email;

        $user->update([
            'name' => $request->name,
            'email' => $nextEmail,
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
        ]);

        // 3. Process Array Fields
        $medical_conditions = $this->processCommaSeparated($request->medical_conditions);
        $medications        = $this->processCommaSeparated($request->medications);
        $allergies          = $this->processCommaSeparated($request->allergies);

        // 4. Update or Create Profile (Direct Model Access)
        $profile = UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'age'                    => $request->age,
                'sex'                    => $request->sex,
                'height'                 => $request->height,
                'weight'                 => $request->weight,
                'phone_number'           => $request->phone_number,
                'address'                => $request->address,
                'username'               => $request->username ?? $user->name,
                'relationship'           => $request->relationship,
                
                'medical_conditions'     => $medical_conditions,
                'medications'            => $medications,
                'allergies'              => $allergies,

                'emergency_name'         => $request->emergency_name,
                'emergency_phone'        => $request->emergency_phone,
                'emergency_relationship' => $request->emergency_relationship,
            ]
        );

        $completion = $this->profileCompletionService->evaluate($profile);

        $profile->update([
            'profile_completed' => $completion['is_complete'],
            'profile_skipped' => $completion['is_complete'] ? false : (bool) ($profile->profile_skipped ?? false),
        ]);

        return redirect()->route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Upload profile photo
     */
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $profile = UserProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return back()->with('error', 'Profile not found');
        }

        // Delete old photo if exists
        if ($profile->profile_photo && Storage::disk('public')->exists($profile->profile_photo)) {
            Storage::disk('public')->delete($profile->profile_photo);
        }

        // Store the new photo
        $path = $request->file('profile_photo')->store('profile-photos', 'public');

        // Update the profile
        $profile->update(['profile_photo' => $path]);

        return back()->with('status', 'photo-updated');
    }

    /**
     * Remove profile photo
     */
    public function removePhoto(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $profile = UserProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return back()->with('error', 'Profile not found');
        }

        // Delete the photo file if exists
        if ($profile->profile_photo && Storage::disk('public')->exists($profile->profile_photo)) {
            Storage::disk('public')->delete($profile->profile_photo);
        }

        // Clear the profile photo path
        $profile->update(['profile_photo' => null]);

        return back()->with('status', 'photo-removed');
    }

    /**
     * Helper to turn comma-separated string into array
     */
    /**
     * Delete the authenticated user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function processCommaSeparated($string)
    {
        if (empty($string)) return [];
        return array_values(array_filter(array_map('trim', explode(',', $string))));
    }
}