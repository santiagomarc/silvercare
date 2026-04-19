<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
        * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        // Handle real-time uniqueness validation
        if ($request->wantsJson() && $request->has('check_unique')) {
            $field = $request->input('check_unique');
            $value = $request->input($field);
            
            if (!$value) {
                return response()->json(['taken' => false]);
            }
            
            $taken = false;
            if ($field === 'email') {
                $taken = DB::table('users')->where('email', 'ILIKE', $value)->exists();
            } elseif ($field === 'username') {
                $taken = DB::table('user_profiles')->where('username', 'ILIKE', $value)->exists();
            }
            
            return response()->json(['taken' => $taken]);
        }

        // Validate elderly registration data
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'user_type' => ['required', 'in:elderly,caregiver'],
            'username' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'sex' => ['nullable', 'in:Male,Female,male,female'],
            'address' => ['nullable', 'string', 'max:500'],
            'age' => ['nullable', 'integer', 'min:1', 'max:150'],
        ]);

        try {
            DB::beginTransaction();

            // Create user account
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $isCaregiver = $validated['user_type'] === 'caregiver';

            // Create role profile
            UserProfile::create([
                'user_id' => $user->id,
                'user_type' => $validated['user_type'],
                'username' => $validated['username'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'sex' => isset($validated['sex']) ? ucfirst(strtolower($validated['sex'])) : null,
                'address' => $validated['address'] ?? null,
                'age' => $validated['age'] ?? null,
                'profile_completed' => $isCaregiver,
                'is_active' => true,
            ]);

            event(new Registered($user));

            DB::commit();

            Auth::login($user);

            return $isCaregiver
                ? redirect()->route('caregiver.dashboard')->with('success', 'Account created successfully.')
                : redirect()->route('dashboard')->with('success', 'Account created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Registration failed: ' . $e->getMessage()]);
        }
    }
}
