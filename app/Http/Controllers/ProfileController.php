<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
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
       
        $user->update([
            'name'  => $request->name,
            'email' => $request->email ?? $user->email, 
        ]);

        // 3. Process Array Fields
        $medical_conditions = $this->processCommaSeparated($request->medical_conditions);
        $medications        = $this->processCommaSeparated($request->medications);
        $allergies          = $this->processCommaSeparated($request->allergies);

        // 4. Update or Create Profile (Direct Model Access)
        UserProfile::updateOrCreate(
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

        return back()->with('status', 'profile-updated');
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
    private function processCommaSeparated($string)
    {
        if (empty($string)) return [];
        return array_values(array_filter(array_map('trim', explode(',', $string))));
    }
}