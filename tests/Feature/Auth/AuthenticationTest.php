<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('auth.select-role', absolute: false));
    }

    public function test_users_with_profiles_are_redirected_to_role_dashboard_on_login(): void
    {
        $elderly = User::factory()->create();

        UserProfile::create([
            'user_id' => $elderly->id,
            'user_type' => 'elderly',
            'username' => 'elderly-user',
            'profile_completed' => true,
            'is_active' => true,
        ]);

        $elderlyLogin = $this->post('/login', [
            'email' => $elderly->email,
            'password' => 'password',
        ]);

        $elderlyLogin->assertRedirect(route('dashboard', absolute: false));
        $this->post('/logout');

        $caregiver = User::factory()->create();

        UserProfile::create([
            'user_id' => $caregiver->id,
            'user_type' => 'caregiver',
            'username' => 'caregiver-user',
            'profile_completed' => true,
            'is_active' => true,
        ]);

        $caregiverLogin = $this->post('/login', [
            'email' => $caregiver->email,
            'password' => 'password',
        ]);

        $caregiverLogin->assertRedirect(route('caregiver.dashboard', absolute: false));
    }

    public function test_caregiver_login_rejects_elderly_intended_url(): void
    {
        $caregiver = User::factory()->create();

        UserProfile::create([
            'user_id' => $caregiver->id,
            'user_type' => 'caregiver',
            'username' => 'caregiver-intended',
            'profile_completed' => true,
            'is_active' => true,
        ]);

        $response = $this->withSession(['url.intended' => '/dashboard'])
            ->post('/login', [
                'email' => $caregiver->email,
                'password' => 'password',
            ]);

        $response->assertRedirect(route('caregiver.dashboard', absolute: false));
    }

    public function test_elderly_login_rejects_caregiver_intended_url(): void
    {
        $elderly = User::factory()->create();

        UserProfile::create([
            'user_id' => $elderly->id,
            'user_type' => 'elderly',
            'username' => 'elderly-intended',
            'profile_completed' => true,
            'is_active' => true,
        ]);

        $response = $this->withSession(['url.intended' => '/caregiver/dashboard'])
            ->post('/login', [
                'email' => $elderly->email,
                'password' => 'password',
            ]);

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_unknown_role_profile_redirects_to_role_selection_on_login(): void
    {
        $user = User::factory()->create();

        UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'invalid-role',
            'username' => 'invalid-role-user',
            'profile_completed' => true,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('auth.select-role', absolute: false));
    }

    public function test_unknown_role_profile_is_redirected_to_role_selection_from_protected_routes(): void
    {
        $user = User::factory()->create();

        UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'invalid-role',
            'username' => 'invalid-route-user',
            'profile_completed' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('welcome'))
            ->assertRedirect(route('auth.select-role'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('auth.select-role'));

        $this->actingAs($user)
            ->get(route('caregiver.dashboard'))
            ->assertRedirect(route('auth.select-role'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
