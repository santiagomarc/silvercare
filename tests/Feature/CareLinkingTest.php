<?php

namespace Tests\Feature;

use App\Models\LinkCode;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CareLinkingTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function makeCaregiverWithCode(string $code = '123456'): array
    {
        $caregiverUser = User::factory()->create();
        $caregiverProfile = UserProfile::create([
            'user_id'           => $caregiverUser->id,
            'user_type'         => 'caregiver',
            'username'          => 'caregiver-test',
            'profile_completed' => true,
            'profile_skipped'   => false,
            'is_active'         => true,
        ]);
        $linkCode = LinkCode::create([
            'code'               => $code,
            'caregiver_profile_id' => $caregiverProfile->id,
            'expires_at'         => now()->addHours(6),
        ]);
        return [$caregiverUser, $caregiverProfile, $linkCode];
    }

    private function makeElderlyUser(): array
    {
        $elderlyUser = User::factory()->create();
        $elderlyProfile = UserProfile::create([
            'user_id'           => $elderlyUser->id,
            'user_type'         => 'elderly',
            'username'          => 'elderly-test',
            'profile_completed' => false,
            'profile_skipped'   => true,   // bypass onboarding gate
            'is_active'         => true,
        ]);
        return [$elderlyUser, $elderlyProfile];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Generate PIN
    // ─────────────────────────────────────────────────────────────────────────

    public function test_caregiver_can_generate_a_linking_pin(): void
    {
        $caregiverUser = User::factory()->create();
        $caregiverProfile = UserProfile::create([
            'user_id'           => $caregiverUser->id,
            'user_type'         => 'caregiver',
            'username'          => 'caregiver-1',
            'profile_completed' => true,
            'is_active'         => true,
        ]);

        $response = $this->actingAs($caregiverUser)
            ->post(route('caregiver.link-code.generate'));

        $response->assertRedirect();
        $response->assertSessionHas('link_code');

        $this->assertDatabaseHas('link_codes', [
            'caregiver_profile_id' => $caregiverProfile->id,
            'used_at'              => null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step 1: Validate PIN (AJAX)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_elderly_can_validate_a_valid_pin(): void
    {
        [, , $linkCode]             = $this->makeCaregiverWithCode('654321');
        [$elderlyUser]              = $this->makeElderlyUser();

        $response = $this->actingAs($elderlyUser)
            ->postJson(route('elderly.validate-link-code'), ['code' => '654321']);

        $response->assertOk()
            ->assertJson(['valid' => true])
            ->assertJsonStructure(['valid', 'code', 'caregiver_name', 'expires_at']);
    }

    public function test_elderly_gets_error_for_invalid_pin(): void
    {
        [$elderlyUser] = $this->makeElderlyUser();

        $response = $this->actingAs($elderlyUser)
            ->postJson(route('elderly.validate-link-code'), ['code' => '000000']);

        $response->assertOk()
            ->assertJson(['valid' => false]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step 2: Confirm link
    // ─────────────────────────────────────────────────────────────────────────

    public function test_elderly_can_link_to_caregiver_using_valid_pin(): void
    {
        [, $caregiverProfile, $linkCode] = $this->makeCaregiverWithCode('123456');
        [$elderlyUser, $elderlyProfile]  = $this->makeElderlyUser();

        $response = $this->actingAs($elderlyUser)
            ->post(route('elderly.confirm-link'), ['code' => '123456']);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertSame($caregiverProfile->id, $elderlyProfile->fresh()->caregiver_id);
        $this->assertNotNull($linkCode->fresh()->used_at);
        $this->assertSame($elderlyProfile->id, $linkCode->fresh()->used_by_profile_id);
    }

    public function test_elderly_cannot_confirm_an_invalid_pin(): void
    {
        [$elderlyUser] = $this->makeElderlyUser();

        $response = $this->actingAs($elderlyUser)
            ->post(route('elderly.confirm-link'), ['code' => '999999']);

        $response->assertRedirect();
        $response->assertSessionHasErrors('code');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Unlink
    // ─────────────────────────────────────────────────────────────────────────

    public function test_elderly_can_unlink_from_caregiver(): void
    {
        [, $caregiverProfile] = $this->makeCaregiverWithCode();
        [$elderlyUser, $elderlyProfile] = $this->makeElderlyUser();

        // Pre-link
        $elderlyProfile->update(['caregiver_id' => $caregiverProfile->id]);

        $response = $this->actingAs($elderlyUser)
            ->post(route('elderly.unlink-caregiver'), [
                'password' => 'password',
            ]);

        $response->assertRedirect();
        $this->assertNull($elderlyProfile->fresh()->caregiver_id);

        $this->assertDatabaseHas('notifications', [
            'elderly_id' => $elderlyProfile->id,
            'type' => 'caregiver_unlinked',
        ]);
    }

    public function test_unlink_when_not_linked_is_graceful(): void
    {
        [$elderlyUser] = $this->makeElderlyUser();

        $response = $this->actingAs($elderlyUser)
            ->post(route('elderly.unlink-caregiver'), [
                'password' => 'password',
            ]);

        // Should not crash — just redirect with info message
        $response->assertRedirect();
        $response->assertSessionHas('info');
    }

    public function test_elderly_cannot_unlink_with_wrong_password(): void
    {
        [, $caregiverProfile] = $this->makeCaregiverWithCode();
        [$elderlyUser, $elderlyProfile] = $this->makeElderlyUser();

        $elderlyProfile->update(['caregiver_id' => $caregiverProfile->id]);

        $response = $this->actingAs($elderlyUser)
            ->from(route('dashboard'))
            ->post(route('elderly.unlink-caregiver'), [
                'password' => 'wrong-password',
            ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHasErrors('password');
        $this->assertSame($caregiverProfile->id, $elderlyProfile->fresh()->caregiver_id);
    }

    public function test_signed_qr_link_prefills_dashboard_link_code(): void
    {
        [, $caregiverProfile] = $this->makeCaregiverWithCode('777777');
        [$elderlyUser] = $this->makeElderlyUser();

        $signedUrl = URL::temporarySignedRoute('elderly.link', now()->addMinutes(15), [
            'code' => '777777',
            'caregiver' => $caregiverProfile->id,
        ]);

        $response = $this->actingAs($elderlyUser)->get($signedUrl);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('prefill_link_code', '777777');
    }
}

