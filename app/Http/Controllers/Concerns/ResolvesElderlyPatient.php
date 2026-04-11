<?php

namespace App\Http\Controllers\Concerns;

use App\Models\UserProfile;
use Illuminate\Support\Collection;

trait ResolvesElderlyPatient
{
    /**
     * Get linked elderly patients for a caregiver profile.
     *
     * @return Collection<int, UserProfile>
     */
    protected function caregiverPatients(?UserProfile $caregiver): Collection
    {
        if (!$caregiver) {
            return collect();
        }

        return $caregiver->elderlyPatients()->with('user')->orderBy('id')->get();
    }

    protected function resolveSelectedPatient(Collection $elderlyPatients, ?int $selectedElderlyId): ?UserProfile
    {
        if ($elderlyPatients->isEmpty()) {
            return null;
        }

        if ($selectedElderlyId) {
            $selected = $elderlyPatients->firstWhere('id', $selectedElderlyId);
            if ($selected) {
                return $selected;
            }
        }

        return $elderlyPatients->first();
    }
}
