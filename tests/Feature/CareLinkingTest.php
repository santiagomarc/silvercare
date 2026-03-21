<?php

namespace Tests\Feature;

use App\Models\LinkCode;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareLinkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_caregiver_can_generate_a_linking_pin(): void
    {
        $caregiverUser = User::factory()->create();
        $caregiverProfile = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'caregiver-1',
            'profile_completed' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($caregiverUser)
            ->post(route('caregiver.link-code.generate'));

        $response->assertRedirect();
        $response->assertSessionHas('link_code');

        $this->assertDatabaseHas('link_codes', [
            'caregiver_profile_id' => $caregiverProfile->id,
            'used_at' => null,
        ]);
    }

    public function test_elderly_can_link_to_caregiver_using_valid_pin(): void
    {
        $caregiverUser = User::factory()->create();
        $caregiverProfile = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'caregiver-2',
            'profile_completed' => true,
            'is_active' => true,
        ]);

        $elderlyUser = User::factory()->create();
        $elderlyProfile = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'elderly-1',
            'profile_completed' => false,
            'is_active' => true,
        ]);

        $linkCode = LinkCode::create([
            'code' => '123456',
            'caregiver_profile_id' => $caregiverProfile->id,
            'expires_at' => now()->addHours(6),
        ]);

        $response = $this->actingAs($elderlyUser)
            ->post(route('elderly.link-caregiver'), [
                'code' => '123456',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertSame($caregiverProfile->id, $elderlyProfile->fresh()->caregiver_id);
        $this->assertNotNull($linkCode->fresh()->used_at);
        $this->assertSame($elderlyProfile->id, $linkCode->fresh()->used_by_profile_id);
    }
}
