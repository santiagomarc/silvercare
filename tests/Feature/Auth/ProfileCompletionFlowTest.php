<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileCompletionFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeElderlyUser(array $profileOverrides = []): User
    {
        $user = User::factory()->create();

        UserProfile::create(array_merge([
            'user_id' => $user->id,
            'user_type' => 'elderly',
            'username' => 'completion-flow-user',
            'profile_completed' => false,
            'profile_skipped' => false,
            'is_active' => true,
        ], $profileOverrides));

        return $user;
    }

    public function test_wizard_is_accessible_when_profile_completed_flag_is_stale_but_data_is_incomplete(): void
    {
        $user = $this->makeElderlyUser([
            'profile_completed' => true,
            'profile_skipped' => true,
            'age' => null,
            'weight' => null,
            'height' => null,
        ]);

        $response = $this->actingAs($user)->get(route('profile.completion'));

        $response->assertOk();
        $response->assertSee('Complete Your Profile');
    }

    public function test_incomplete_profile_submission_resets_skip_and_keeps_completion_false(): void
    {
        $user = $this->makeElderlyUser([
            'profile_completed' => false,
            'profile_skipped' => true,
        ]);

        $response = $this->actingAs($user)->post(route('profile.completion.store'), [
            'age' => 70,
            'weight' => 67,
            'height' => 165,
            'emergency_name' => 'Jane Doe',
            'emergency_phone' => '+639171111111',
            'emergency_relationship' => 'Daughter',
            'conditions' => '',
            'medications' => '',
            'allergies' => '',
        ]);

        $response->assertRedirect(route('profile.completion'));

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'profile_completed' => false,
            'profile_skipped' => false,
        ]);
    }

    public function test_complete_profile_submission_marks_profile_as_completed_and_unskipped(): void
    {
        $user = $this->makeElderlyUser([
            'profile_completed' => false,
            'profile_skipped' => true,
        ]);

        $response = $this->actingAs($user)->post(route('profile.completion.store'), [
            'age' => 70,
            'weight' => 67,
            'height' => 165,
            'emergency_name' => 'Jane Doe',
            'emergency_phone' => '+639171111111',
            'emergency_relationship' => 'Daughter',
            'conditions' => 'Hypertension',
            'medications' => '',
            'allergies' => '',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'profile_completed' => true,
            'profile_skipped' => false,
        ]);
    }
}