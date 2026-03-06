<?php

namespace App\Policies;

use App\Models\Medication;
use App\Models\User;

class MedicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->profile !== null;
    }

    public function create(User $user): bool
    {
        return $user->profile?->isCaregiver() === true;
    }

    public function view(User $user, Medication $medication): bool
    {
        $profile = $user->profile;

        return $profile !== null
            && ($profile->id === $medication->caregiver_id || $profile->id === $medication->elderly_id);
    }

    public function update(User $user, Medication $medication): bool
    {
        return $user->profile?->id === $medication->caregiver_id;
    }

    public function delete(User $user, Medication $medication): bool
    {
        return $user->profile?->id === $medication->caregiver_id;
    }

    public function take(User $user, Medication $medication): bool
    {
        return $user->profile?->id === $medication->elderly_id;
    }
}