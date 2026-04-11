<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_profile_update_sets_profile_completed_when_all_required_sections_are_present(): void
    {
        $user = User::factory()->create();

        UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'elderly',
            'username' => 'profile-completion-sync',
            'profile_completed' => false,
            'profile_skipped' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'age' => 71,
            'height' => 166,
            'weight' => 69,
            'emergency_name' => 'Emergency Contact',
            'emergency_phone' => '+639189999999',
            'emergency_relationship' => 'Son',
            'medical_conditions' => 'Hypertension',
            'medications' => '',
            'allergies' => '',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'profile_completed' => true,
            'profile_skipped' => false,
        ]);
    }

    public function test_profile_photo_can_be_uploaded(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'elderly',
            'username' => 'photo-upload-user',
            'profile_completed' => true,
            'profile_skipped' => false,
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user)
            ->post('/profile/photo', ['profile_photo' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'photo-updated');

        $profile = UserProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertNotNull($profile->profile_photo);
        $this->assertTrue(Storage::disk('public')->exists($profile->profile_photo));
    }

    public function test_profile_photo_upload_requires_image_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'elderly',
            'username' => 'photo-upload-invalid',
            'profile_completed' => true,
            'profile_skipped' => false,
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->create('not-an-image.txt', 5, 'text/plain');

        $response = $this->actingAs($user)
            ->from('/profile')
            ->post('/profile/photo', ['profile_photo' => $file]);

        $response->assertRedirect('/profile');
        $response->assertSessionHasErrors('profile_photo');
    }
}
