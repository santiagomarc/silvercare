<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UploadProfilePhotoRequest;
use App\Models\UserProfile;
use App\Support\CommaSeparatedValueParser;
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
    public function update(ProfileUpdateRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validated();

        // 2. Update User Table (Name/Email)
       
        $nextEmail = $validated['email'] ?? $user->email;
        $emailChanged = $nextEmail !== $user->email;

        $user->update([
            'name' => $validated['name'],
            'email' => $nextEmail,
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
        ]);

        // 3. Process Array Fields
        $medical_conditions = CommaSeparatedValueParser::parse($validated['medical_conditions'] ?? null);
        $medications        = CommaSeparatedValueParser::parse($validated['medications'] ?? null);
        $allergies          = CommaSeparatedValueParser::parse($validated['allergies'] ?? null);

        // 4. Update or Create Profile (Direct Model Access)
        $profile = UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'age'                    => $validated['age'] ?? null,
                'sex'                    => $validated['sex'] ?? null,
                'height'                 => $validated['height'] ?? null,
                'weight'                 => $validated['weight'] ?? null,
                'phone_number'           => $validated['phone_number'] ?? null,
                'address'                => $validated['address'] ?? null,
                'username'               => $validated['username'] ?? $user->name,
                'relationship'           => $validated['relationship'] ?? null,
                
                'medical_conditions'     => $medical_conditions,
                'medications'            => $medications,
                'allergies'              => $allergies,

                'emergency_name'         => $validated['emergency_name'] ?? null,
                'emergency_phone'        => $validated['emergency_phone'] ?? null,
                'emergency_relationship' => $validated['emergency_relationship'] ?? null,
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
    public function uploadPhoto(UploadProfilePhotoRequest $request)
    {
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
     * Delete the authenticated user's account.
     */
    public function destroy(DeleteAccountRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

}