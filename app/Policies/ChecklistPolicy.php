<?php

namespace App\Policies;

use App\Models\Checklist;
use App\Models\User;

class ChecklistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->profile !== null;
    }

    public function create(User $user): bool
    {
        return $user->profile?->isCaregiver() === true;
    }

    public function view(User $user, Checklist $checklist): bool
    {
        $profile = $user->profile;

        return $profile !== null
            && ($profile->id === $checklist->caregiver_id || $profile->id === $checklist->elderly_id);
    }

    public function update(User $user, Checklist $checklist): bool
    {
        return $user->profile?->id === $checklist->caregiver_id;
    }

    public function delete(User $user, Checklist $checklist): bool
    {
        return $user->profile?->id === $checklist->caregiver_id;
    }

    public function toggleCompletion(User $user, Checklist $checklist): bool
    {
        $profile = $user->profile;

        return $profile !== null
            && ($profile->id === $checklist->elderly_id || $profile->id === $checklist->caregiver_id);
    }
}