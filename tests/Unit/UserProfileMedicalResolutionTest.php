<?php

namespace Tests\Unit;

use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileMedicalResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolved_lists_prefer_primary_columns_over_legacy_fallback(): void
    {
        $profile = new UserProfile([
            'medical_conditions' => ['Hypertension', 'Diabetes'],
            'medications' => ['Metformin'],
            'allergies' => ['Peanuts'],
            'medical_info' => [
                'conditions' => ['Legacy Condition'],
                'medications' => ['Legacy Medication'],
                'allergies' => ['Legacy Allergy'],
            ],
        ]);

        $this->assertSame(['Hypertension', 'Diabetes'], $profile->resolvedMedicalConditions());
        $this->assertSame(['Metformin'], $profile->resolvedMedications());
        $this->assertSame(['Peanuts'], $profile->resolvedAllergies());
    }

    public function test_resolved_lists_fallback_to_legacy_medical_info_when_primary_is_empty(): void
    {
        $profile = new UserProfile([
            'medical_conditions' => [],
            'medications' => null,
            'allergies' => '',
            'medical_info' => [
                'conditions' => ['Asthma'],
                'medications' => ['Albuterol'],
                'allergies' => ['Dust'],
            ],
        ]);

        $this->assertSame(['Asthma'], $profile->resolvedMedicalConditions());
        $this->assertSame(['Albuterol'], $profile->resolvedMedications());
        $this->assertSame(['Dust'], $profile->resolvedAllergies());
    }

    public function test_resolved_lists_normalize_json_and_csv_strings(): void
    {
        $profile = new UserProfile([
            'medical_conditions' => '"not-json"',
            'medications' => '["Aspirin", "  Zinc  "]',
            'allergies' => 'Pollen, Dust,',
        ]);

        $this->assertSame(['"not-json"'], $profile->resolvedMedicalConditions());
        $this->assertSame(['Aspirin', 'Zinc'], $profile->resolvedMedications());
        $this->assertSame(['Pollen', 'Dust'], $profile->resolvedAllergies());
    }
}
