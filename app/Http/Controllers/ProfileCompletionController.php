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
        $user = Auth::user();
        $profile = $user->profile;

        $completion = $this->profileCompletionService->evaluate($profile);

        // If truly complete, redirect to dashboard
        if ($completion['is_complete']) {
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
    public function store(StoreProfileCompletionRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        $validated = $request->validated();

        // Prepare medical info as separate arrays
        $medicalConditions = CommaSeparatedValueParser::parse($validated['conditions'] ?? null);
        $medicationsArray = CommaSeparatedValueParser::parse($validated['medications'] ?? null);
        $allergiesArray = CommaSeparatedValueParser::parse($validated['allergies'] ?? null);

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
        ]);

        $completion = $this->profileCompletionService->evaluate($profile->fresh());

        $profile->update([
            'profile_completed' => $completion['is_complete'],
            'profile_skipped' => false,
        ]);

        if (! $completion['is_complete']) {
            return redirect()->route('profile.completion')
                ->with('info', 'Please complete all profile sections before continuing.');
        }

        return $this->redirectToDashboard($profile->user_type)
            ->with('success', 'Profile completed successfully!');
    }

    /**
     * Skip profile completion for now.
     *
     * Sets profile_skipped = true so the middleware does not redirect the user
     * again. Their dashboard will show a nudge banner to encourage completion.
     * profile_completed intentionally stays false so we can distinguish between
     * "genuinely done" and "skipped" in reporting and nudge logic.
     */
    public function skip(): RedirectResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        $profile->update([
            'profile_skipped' => true,
        ]);

        return $this->redirectToDashboard($profile->user_type)
            ->with('info', 'Profile completion skipped. You can complete it later from your settings.');
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
