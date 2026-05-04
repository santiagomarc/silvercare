<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfileCompletionRequest;
use App\Services\ProfileCompletionService;
use App\Support\CommaSeparatedValueParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProfileCompletionController extends Controller
{
    public function __construct(
        protected ProfileCompletionService $profileCompletionService,
    ) {
    }

    /**
     * Display the profile completion form (3-step wizard).
     */
    public function show(): View|RedirectResponse
    {
        // Require authentication
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $profile = $user->profile;

        $completion = $this->profileCompletionService->evaluate($profile);

        // If already complete, redirect to dashboard
        if ($completion['is_complete']) {
            return $this->redirectToDashboard($profile->user_type);
        }

        $caregiver = null;

        return view('auth.profile-completion', compact('caregiver'));
    }

    /**
     * Handle profile completion submission.
     */
    public function store(StoreProfileCompletionRequest $request): RedirectResponse
    {
        // Require authentication
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $profile = $user->profile;

        $validated = $request->validated();

        // Prepare medical info as separate arrays
        $medicalConditions = CommaSeparatedValueParser::parse($validated['conditions'] ?? null);
        $medicationsArray = CommaSeparatedValueParser::parse($validated['medications'] ?? null);
        $allergiesArray = CommaSeparatedValueParser::parse($validated['allergies'] ?? null);

        // Update profile with all fields
        $profile->update([
            'age' => $validated['age'],
            'weight' => $validated['weight'],
            'height' => $validated['height'],
            
            // Emergency Contact
            'emergency_name' => $validated['emergency_name'],
            'emergency_phone' => $validated['emergency_phone'],
            'emergency_relationship' => $validated['emergency_relationship'],
            
            // Medical Info - JSON Arrays
            'medical_conditions' => $medicalConditions,
            'medications' => $medicationsArray,
            'allergies' => $allergiesArray,
        ]);

        $completion = $this->profileCompletionService->evaluate($profile->fresh());

        // Update profile_completed based on completion evaluation
        $profile->update([
            'profile_completed' => $completion['is_complete'],
        ]);

        if (!$completion['is_complete']) {
            return redirect()->route('profile.completion')
                ->with('info', 'Please complete all profile sections before continuing.');
        }

        return $this->redirectToDashboard($profile->user_type)
            ->with('success', 'Profile completed successfully!');
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
