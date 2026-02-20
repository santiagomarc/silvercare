<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Create a new user with profile (elderly or caregiver)
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Create user account
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // Create profile
            $user->profile()->create([
                'user_type' => $data['user_type'], // 'elderly' or 'caregiver'
                'username' => $data['username'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'relationship' => $data['relationship'] ?? null, // For caregivers
            ]);

            return $user->load('profile');
        });
    }

    /**
     * Update user profile (for elderly users)
     */
    public function updateElderlyProfile(UserProfile $profile, array $data): UserProfile
    {
        $profile->update([
            'username' => $data['username'] ?? $profile->username,
            'phone_number' => $data['phone_number'] ?? $profile->phone_number,
            'sex' => $data['sex'] ?? $profile->sex,
            'age' => $data['age'] ?? $profile->age,
            'weight' => $data['weight'] ?? $profile->weight,
            'height' => $data['height'] ?? $profile->height,
            'emergency_contact' => $data['emergency_contact'] ?? $profile->emergency_contact,
            'medical_info' => $data['medical_info'] ?? $profile->medical_info,
            'profile_completed' => $data['profile_completed'] ?? true,
        ]);

        return $profile->fresh();
    }

    /**
     * Update caregiver profile
     */
    public function updateCaregiverProfile(UserProfile $profile, array $data): UserProfile
    {
        $profile->update([
            'phone_number' => $data['phone_number'] ?? $profile->phone_number,
            'relationship' => $data['relationship'] ?? $profile->relationship,
            'profile_completed' => $data['profile_completed'] ?? true,
        ]);

        return $profile->fresh();
    }

    /**
     * Link caregiver to elderly (1:1 relationship)
     */
    public function linkCaregiverToElderly(int $elderlyProfileId, int $caregiverProfileId): bool
    {
        $elderlyProfile = UserProfile::findOrFail($elderlyProfileId);
        $caregiverProfile = UserProfile::findOrFail($caregiverProfileId);

        // Verify types
        if (!$elderlyProfile->isElderly() || !$caregiverProfile->isCaregiver()) {
            throw new \Exception('Invalid user types for linking');
        }

        // Check if elderly already has a caregiver
        if ($elderlyProfile->caregiver_id) {
            throw new \Exception('Elderly user already has a caregiver assigned');
        }

        // Check if caregiver already has an elderly
        if ($caregiverProfile->elderly()->exists()) {
            throw new \Exception('Caregiver is already assigned to another elderly user');
        }

        // Link them
        $elderlyProfile->update(['caregiver_id' => $caregiverProfileId]);

        return true;
    }

    /**
     * Unlink caregiver from elderly
     */
    public function unlinkCaregiverFromElderly(int $elderlyProfileId): bool
    {
        $elderlyProfile = UserProfile::findOrFail($elderlyProfileId);
        $elderlyProfile->update(['caregiver_id' => null]);

        return true;
    }

    /**
     * Get elderly profile with caregiver
     */
    public function getElderlyWithCaregiver(int $profileId): UserProfile
    {
        return UserProfile::with('caregiver')
            ->where('id', $profileId)
            ->where('user_type', 'elderly')
            ->firstOrFail();
    }

    /**
     * Get caregiver profile with assigned elderly
     */
    public function getCaregiverWithElderly(int $profileId): UserProfile
    {
        return UserProfile::with('elderly')
            ->where('id', $profileId)
            ->where('user_type', 'caregiver')
            ->firstOrFail();
    }

    /**
     * Check if caregiver is assigned to an elderly
     */
    public function caregiverIsAssigned(int $caregiverProfileId): bool
    {
        $caregiver = UserProfile::findOrFail($caregiverProfileId);
        return $caregiver->elderly()->exists();
    }

    /**
     * Get all elderly users without caregivers
     */
    public function getUnassignedElderly()
    {
        return UserProfile::where('user_type', 'elderly')
            ->whereNull('caregiver_id')
            ->with('user')
            ->get();
    }

    /**
     * Get all caregivers without assigned elderly
     */
    public function getUnassignedCaregivers()
    {
        return UserProfile::where('user_type', 'caregiver')
            ->whereDoesntHave('elderly')
            ->with('user')
            ->get();
    }
}

