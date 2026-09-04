<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CaregiverSetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function createCaregiverUser(): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('unusable-temporary-password'),
        ]);

        UserProfile::create([
            'user_id'           => $user->id,
            'user_type'         => 'caregiver',
            'username'          => 'caregiver_' . $user->id,
            'profile_completed' => true,
            'profile_skipped'   => false,
            'is_active'         => true,
        ]);

        return $user;
    }

    public function test_caregiver_set_password_screen_cannot_be_rendered_without_signature(): void
    {
        $caregiver = $this->createCaregiverUser();

        $response = $this->get(route('caregiver.password.show', $caregiver->id));

        $response->assertStatus(403);
    }

    public function test_caregiver_set_password_screen_cannot_be_rendered_for_non_caregiver(): void
    {
        $user = User::factory()->create();
        UserProfile::create([
            'user_id'           => $user->id,
            'user_type'         => 'elderly',
            'username'          => 'elderly_' . $user->id,
            'profile_completed' => true,
            'profile_skipped'   => false,
            'is_active'         => true,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'caregiver.password.show',
            now()->addDays(7),
            ['userId' => $user->id]
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(403);
    }

    public function test_caregiver_set_password_screen_can_be_rendered_with_valid_signature_and_contains_signed_action(): void
    {
        $caregiver = $this->createCaregiverUser();

        $signedUrl = URL::temporarySignedRoute(
            'caregiver.password.show',
            now()->addDays(7),
            ['userId' => $caregiver->id]
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertSee('Set your password');
        $response->assertSee($caregiver->email);

        // The form action must be a signed URL containing signature=
        $expectedActionPrefix = route('caregiver.password.store', $caregiver->id);
        $response->assertSee($expectedActionPrefix);
        $response->assertSee('signature=');
    }

    public function test_caregiver_cannot_store_password_without_signature(): void
    {
        $caregiver = $this->createCaregiverUser();

        $response = $this->post(route('caregiver.password.store', $caregiver->id), [
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'ValidPassword123!',
        ]);

        $response->assertStatus(403);
    }

    public function test_caregiver_cannot_store_password_with_mismatched_confirmation(): void
    {
        $caregiver = $this->createCaregiverUser();

        $signedStoreUrl = URL::temporarySignedRoute(
            'caregiver.password.store',
            now()->addDays(7),
            ['userId' => $caregiver->id]
        );

        $response = $this->post($signedStoreUrl, [
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'DifferentPassword123!',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertFalse(Hash::check('ValidPassword123!', $caregiver->fresh()->password));
        $this->assertGuest();
    }

    public function test_caregiver_can_store_password_with_valid_signed_url(): void
    {
        $caregiver = $this->createCaregiverUser();

        // 1. Visit the show page using a signed URL
        $signedShowUrl = URL::temporarySignedRoute(
            'caregiver.password.show',
            now()->addDays(7),
            ['userId' => $caregiver->id]
        );

        $showResponse = $this->get($signedShowUrl);
        $showResponse->assertStatus(200);

        // 2. Extract or generate the signed store URL
        $submitUrl = URL::temporarySignedRoute(
            'caregiver.password.store',
            now()->addDays(7),
            ['userId' => $caregiver->id]
        );

        $response = $this->post($submitUrl, [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('caregiver.dashboard'));

        // Check password has been updated
        $this->assertTrue(Hash::check('NewPassword123!', $caregiver->fresh()->password));

        // Check user is authenticated
        $this->assertAuthenticatedAs($caregiver);
    }

    public function test_caregiver_can_submit_form_action_extracted_directly_from_rendered_page(): void
    {
        $caregiver = $this->createCaregiverUser();

        $signedShowUrl = URL::temporarySignedRoute(
            'caregiver.password.show',
            now()->addDays(7),
            ['userId' => $caregiver->id]
        );

        $showResponse = $this->get($signedShowUrl);
        $showResponse->assertStatus(200);

        // Extract form action attribute from HTML
        preg_match('/<form[^>]+action="([^"]+)"/i', $showResponse->getContent(), $matches);
        $this->assertNotEmpty($matches[1], 'Form action attribute should not be empty.');
        $formActionUrl = html_entity_decode($matches[1]);

        $this->assertStringContainsString('signature=', $formActionUrl);

        // Submit to the exact form action extracted from the page
        $postResponse = $this->post($formActionUrl, [
            'password' => 'SecurePass123$',
            'password_confirmation' => 'SecurePass123$',
        ]);

        $postResponse->assertSessionHasNoErrors();
        $postResponse->assertRedirect(route('caregiver.dashboard'));

        $this->assertTrue(Hash::check('SecurePass123$', $caregiver->fresh()->password));
        $this->assertAuthenticatedAs($caregiver);
    }
}
