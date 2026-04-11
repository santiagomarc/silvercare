<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileCompletionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function makeElderlyUser(array $profileOverrides = []): User
    {
        $user = User::factory()->create();
        UserProfile::create(array_merge([
            'user_id'           => $user->id,
            'user_type'         => 'elderly',
            'username'          => 'test-elderly',
            'profile_completed' => false,
            'profile_skipped'   => false,
            'is_active'         => true,
        ], $profileOverrides));
        return $user;
    }

    private function makeCaregiverUser(array $profileOverrides = []): User
    {
        $user = User::factory()->create();
        UserProfile::create(array_merge([
            'user_id'           => $user->id,
            'user_type'         => 'caregiver',
            'username'          => 'test-caregiver',
            'profile_completed' => false,
            'profile_skipped'   => false,
            'is_active'         => true,
        ], $profileOverrides));
        return $user;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Elderly — blocked when not completed and not skipped
    // ─────────────────────────────────────────────────────────────────────────

    public function test_elderly_with_incomplete_profile_is_redirected_to_wizard(): void
    {
        $user = $this->makeElderlyUser([
            'profile_completed' => false,
            'profile_skipped'   => false,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('profile.completion'));
    }

    public function test_elderly_with_completed_profile_can_access_dashboard(): void
    {
        $user = $this->makeElderlyUser([
            'profile_completed' => true,
            'profile_skipped'   => false,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        // A completed profile must never be redirected back to the wizard.
        $this->assertNotEquals(
            route('profile.completion'),
            $response->headers->get('Location'),
            'Completed profile was incorrectly redirected to the profile completion wizard.'
        );
        // The dashboard should render (200) or perform its own internal redirect — not the wizard.
        $response->assertStatus(200);
    }

    public function test_elderly_who_skipped_can_access_dashboard(): void
    {
        $user = $this->makeElderlyUser([
            'profile_completed' => false,
            'profile_skipped'   => true,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        // Must NOT be sent back to the wizard
        $this->assertNotEquals(
            route('profile.completion'),
            $response->headers->get('Location')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Caregiver — same logic
    // ─────────────────────────────────────────────────────────────────────────

    public function test_caregiver_with_incomplete_profile_is_redirected_to_wizard(): void
    {
        $user = $this->makeCaregiverUser([
            'profile_completed' => false,
            'profile_skipped'   => false,
        ]);

        $response = $this->actingAs($user)->get(route('caregiver.dashboard'));

        $response->assertRedirect(route('profile.completion'));
    }

    public function test_caregiver_who_skipped_can_access_dashboard(): void
    {
        $user = $this->makeCaregiverUser([
            'profile_completed' => false,
            'profile_skipped'   => true,
        ]);

        $response = $this->actingAs($user)->get(route('caregiver.dashboard'));

        $this->assertNotEquals(
            route('profile.completion'),
            $response->headers->get('Location')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // The wizard routes themselves are always accessible
    // ─────────────────────────────────────────────────────────────────────────

    public function test_profile_completion_wizard_is_accessible_without_completed_profile(): void
    {
        $user = $this->makeElderlyUser([
            'profile_completed' => false,
            'profile_skipped'   => false,
        ]);

        $response = $this->actingAs($user)->get(route('profile.completion'));

        // Should render the wizard, not loop back to itself
        $this->assertNotEquals(
            route('profile.completion'),
            $response->headers->get('Location')
        );
        $response->assertSuccessful();
    }

    public function test_skip_route_sets_profile_skipped_flag(): void
    {
        $user = $this->makeElderlyUser([
            'profile_completed' => false,
            'profile_skipped'   => false,
        ]);

        $this->actingAs($user)->get(route('profile.completion.skip'));

        $this->assertDatabaseHas('user_profiles', [
            'user_id'         => $user->id,
            'profile_skipped' => true,
            // profile_completed must remain false — skipping ≠ completing
            'profile_completed' => false,
        ]);
    }
}
