<?php

namespace App\Services;

use App\Models\UserProfile;

class ProfileCompletionService
{
    /**
     * Evaluate profile completion state from persisted profile fields.
     *
     * @return array<string, bool>
     */
    public function evaluate(?UserProfile $profile): array
    {
        if (! $profile) {
            return [
                'personal_complete' => false,
                'emergency_complete' => false,
                'medical_complete' => false,
                'is_complete' => false,
            ];
        }

        $personalComplete = filled($profile->age)
            && filled($profile->weight)
            && filled($profile->height);

        $emergencyComplete = filled($profile->emergency_name)
            && filled($profile->emergency_phone)
            && filled($profile->emergency_relationship);

        $medicalComplete = ! empty($this->normalizeList($profile->medical_conditions))
            || ! empty($this->normalizeList($profile->medications))
            || ! empty($this->normalizeList($profile->allergies));

        return [
            'personal_complete' => $personalComplete,
            'emergency_complete' => $emergencyComplete,
            'medical_complete' => $medicalComplete,
            'is_complete' => $personalComplete && $emergencyComplete && $medicalComplete,
        ];
    }

    /**
     * @param array<int, string>|string|null $value
     * @return array<int, string>
     */
    private function normalizeList(array|string|null $value): array
    {
        if (is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        }

        return array_values(array_filter((array) $value, fn ($item) => filled($item)));
    }
}