<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\UserService;
use App\Mail\CaregiverInvitation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request for ELDERLY users.
     * Optionally creates a caregiver account and sends password reset email.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate elderly registration data
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'username' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'sex' => ['required', 'in:Male,Female,male,female'],
            'address' => ['nullable', 'string', 'max:500'],
            'age' => ['nullable', 'integer', 'min:1', 'max:150'],
            
            // Optional caregiver invitation
            'add_caregiver' => ['nullable', 'boolean'],
            'caregiver_name' => ['required_if:add_caregiver,true', 'string', 'max:255'],
            'caregiver_email' => ['required_if:add_caregiver,true', 'email', 'max:255', 'unique:users,email'],
            'caregiver_relationship' => ['required_if:add_caregiver,true', 'in:Spouse,Child,Professional Caregiver'],
            'caregiver_phone' => ['nullable', 'string', 'max:20'],
            'caregiver_address' => ['nullable', 'string', 'max:500'],
            'caregiver_age' => ['nullable', 'integer', 'min:1', 'max:150'],
        ]);

        try {
            DB::beginTransaction();

            // Create elderly user account
            $elderlyUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Create elderly profile
            $elderlyProfile = UserProfile::create([
                'user_id' => $elderlyUser->id,
                'user_type' => 'elderly',
                'username' => $validated['username'],
                'phone_number' => $validated['phone_number'],
                'sex' => ucfirst(strtolower($validated['sex'])),
                'address' => $validated['address'] ?? null,
                'age' => $validated['age'] ?? null,
                'profile_completed' => false,
                'is_active' => true,
            ]);

            // Handle caregiver invitation if requested
            $caregiverId = null;
            if ($request->boolean('add_caregiver')) {
                $caregiverId = $this->createCaregiverAccount(
                    elderlyUser: $elderlyUser,
                    caregiverName: $validated['caregiver_name'],
                    caregiverEmail: $validated['caregiver_email'],
                    relationship: $validated['caregiver_relationship'],
                    phone: $validated['caregiver_phone'] ?? null,
                    address: $validated['caregiver_address'] ?? null,
                    age: $validated['caregiver_age'] ?? null
                );
            }

            // Link caregiver if created
            if ($caregiverId) {
                $elderlyProfile->update(['caregiver_id' => $caregiverId]);
            }

            event(new Registered($elderlyUser));

            DB::commit();

            // Log in the elderly user
            Auth::login($elderlyUser);

            // Redirect to profile completion
            return redirect()->route('profile.completion')->with('success', 
                $caregiverId 
                    ? 'Account created! An invitation email has been sent to your caregiver to set their password.' 
                    : 'Account created! Please complete your profile.'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Registration failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Create caregiver account and send invitation email
     */
    protected function createCaregiverAccount(
        User $elderlyUser,
        string $caregiverName,
        string $caregiverEmail,
        string $relationship,
        ?string $phone = null,
        ?string $address = null,
        ?int $age = null
    ): int {
        // Generate temporary secure password
        $tempPassword = Str::random(16);

        // Create caregiver user account
        $caregiverUser = User::create([
            'name' => $caregiverName,
            'email' => $caregiverEmail,
            'password' => Hash::make($tempPassword), // Temporary password
        ]);

        // Create caregiver profile
        $caregiverProfile = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => $caregiverName,
            'relationship' => $relationship,
            'phone_number' => $phone,
            'address' => $address,
            'age' => $age,
            'profile_completed' => true, // Caregivers don't need profile completion
            'is_active' => true,
        ]);

        // Send caregiver invitation email with set password link
        try {
            Mail::to($caregiverEmail)->send(
                new CaregiverInvitation($caregiverUser, $elderlyUser)
            );
        } catch (\Exception $e) {
            // Log error but don't fail registration
            Log::error('Failed to send caregiver invitation email: ' . $e->getMessage());
        }

        return $caregiverProfile->id;
    }
}
