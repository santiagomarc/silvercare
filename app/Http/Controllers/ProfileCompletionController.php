<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProfileCompletionController extends Controller
{
    /**
     * Display the profile completion form (3-step wizard).
     */
    public function show(): View|RedirectResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        // If already completed, redirect to dashboard
        if ($profile && $profile->profile_completed) {
            return $this->redirectToDashboard($profile->user_type);
        }

        // Get caregiver info if exists (for emergency contact auto-fill option)
        $caregiver = null;
        if ($profile && $profile->caregiver_id) {
            $caregiverProfile = $profile->caregiver;
            if ($caregiverProfile && $caregiverProfile->user) {
                $caregiver = [
                    'name' => $caregiverProfile->user->name,
                    'phone' => $caregiverProfile->phone_number ?? '',
                    'relationship' => $caregiverProfile->relationship ?? 'Caregiver',
                ];
            }
        }

        return view('auth.profile-completion', compact('profile', 'caregiver'));
    }

    /**
     * Handle profile completion submission.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        // Validate the complete profile data
        $validated = $request->validate([
            // Step 1: Personal Details
            'age' => ['nullable', 'integer', 'min:1', 'max:150'],
            'weight' => ['nullable', 'numeric', 'min:1', 'max:500'],
            'height' => ['nullable', 'numeric', 'min:1', 'max:300'],
            
            // Step 2: Emergency Contact
            'emergency_name' => ['nullable', 'string', 'max:255'],
            'emergency_phone' => ['nullable', 'string', 'max:20'],
            'emergency_relationship' => ['nullable', 'string', 'max:255'],
            
            // Step 3: Medical Info
            'conditions' => ['nullable', 'string'],
            'medications' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
        ]);

        // Prepare medical info as separate arrays
        $medicalConditions = $validated['conditions'] ? array_values(array_filter(array_map('trim', explode(',', $validated['conditions'])))) : [];
        $medicationsArray = $validated['medications'] ? array_values(array_filter(array_map('trim', explode(',', $validated['medications'])))) : [];
        $allergiesArray = $validated['allergies'] ? array_values(array_filter(array_map('trim', explode(',', $validated['allergies'])))) : [];

        // Update profile with individual columns (not legacy JSON fields)
        $profile->update([
            'age' => $validated['age'],
            'weight' => $validated['weight'],
            'height' => $validated['height'],
            
            // Emergency Contact - Individual Columns
            'emergency_name' => $validated['emergency_name'],
            'emergency_phone' => $validated['emergency_phone'],
            'emergency_relationship' => $validated['emergency_relationship'],
            
            // Medical Info - JSON Arrays
            'medical_conditions' => $medicalConditions,
            'medications' => $medicationsArray,
            'allergies' => $allergiesArray,
            
            'profile_completed' => true,
        ]);

        return redirect()->route('dashboard')->with('success', 'Profile completed successfully!');
    }

    /**
     * Skip profile completion (mark as completed but without data).
     */
    public function skip(): RedirectResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        $profile->update([
            'profile_completed' => true,
        ]);

        return redirect()->route('dashboard')->with('info', 'Profile completion skipped. You can complete it later from your settings.');
    }

    /**
     * Redirect to appropriate dashboard based on user type.
     */
    protected function redirectToDashboard(string $userType): RedirectResponse
    {
        if ($userType === 'elderly') {
            return redirect()->route('dashboard');
        } elseif ($userType === 'caregiver') {
            return redirect()->route('caregiver.dashboard');
        }
        
        return redirect()->route('dashboard');
    }
}
