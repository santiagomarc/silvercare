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
