<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_elderly_dashboard_does_not_render_legacy_connection_or_queue_sections(): void
    {
        $caregiverUser = User::factory()->create();
        $caregiverProfile = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'caregiver-legacy-check',
            'profile_completed' => true,
            'profile_skipped' => false,
            'is_active' => true,
        ]);

        $elderlyUser = User::factory()->create();
        UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'elderly-legacy-check',
            'profile_completed' => true,
            'profile_skipped' => false,
            'is_active' => true,
            'caregiver_id' => $caregiverProfile->id,
            'age' => 70,
            'height' => 165,
            'weight' => 68,
            'emergency_name' => 'Contact Name',
            'emergency_phone' => '+639170000001',
            'emergency_relationship' => 'Daughter',
            'medical_conditions' => ['Hypertension'],
        ]);

        $response = $this->actingAs($elderlyUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Link Your Caregiver');
        $response->assertDontSee('Your caregiver can see your health data');
        $response->assertDontSee('Action Queue');
        $response->assertDontSee('Show Full Today Details');
    }

    public function test_elderly_profile_page_renders_care_connection_section(): void
    {
        $elderlyUser = User::factory()->create();
        UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'elderly-profile-care-connection',
            'profile_completed' => true,
            'profile_skipped' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($elderlyUser)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('Care Connection');
    }
}
