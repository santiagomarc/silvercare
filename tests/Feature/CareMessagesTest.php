<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CareMessagesTest extends TestCase
{
    use RefreshDatabase;

    private function createLinkedProfiles(): array
    {
        $caregiverUser = User::factory()->create();
        $caregiverProfile = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'caregiver-messages',
            'profile_completed' => true,
            'profile_skipped' => false,
        ]);

        $elderlyUser = User::factory()->create();
        $elderlyProfile = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'elderly-messages',
            'profile_completed' => true,
            'profile_skipped' => false,
            'caregiver_id' => $caregiverProfile->id,
        ]);

        return [$caregiverUser, $caregiverProfile, $elderlyUser, $elderlyProfile];
    }

    public function test_caregiver_can_view_messages_page_for_selected_patient(): void
    {
        [$caregiverUser, , , $elderlyProfile] = $this->createLinkedProfiles();

        $response = $this->actingAs($caregiverUser)
            ->get(route('caregiver.messages.index', ['elderly' => $elderlyProfile->id]));

        $response->assertOk();
        $response->assertSee('Care Messages');
    }

    public function test_caregiver_can_send_message_to_linked_elderly(): void
    {
        [$caregiverUser, $caregiverProfile, , $elderlyProfile] = $this->createLinkedProfiles();

        $response = $this->actingAs($caregiverUser)
            ->post(route('caregiver.messages.store'), [
                'elderly_id' => $elderlyProfile->id,
                'message' => 'Please remember your 8 AM medication.',
            ]);

        $response->assertRedirect(route('caregiver.messages.index', ['elderly' => $elderlyProfile->id]));

        $this->assertDatabaseHas('care_messages', [
            'caregiver_id' => $caregiverProfile->id,
            'elderly_id' => $elderlyProfile->id,
            'sender_profile_id' => $caregiverProfile->id,
            'message' => 'Please remember your 8 AM medication.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'elderly_id' => $elderlyProfile->id,
            'type' => 'caregiver_message',
            'title' => 'New message from your caregiver',
        ]);
    }

    public function test_caregiver_cannot_send_message_to_unlinked_elderly(): void
    {
        [$caregiverUser, , , ] = $this->createLinkedProfiles();

        $otherElderlyUser = User::factory()->create();
        $otherElderlyProfile = UserProfile::create([
            'user_id' => $otherElderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'unlinked-elderly',
            'profile_completed' => true,
            'profile_skipped' => false,
        ]);

        $response = $this->actingAs($caregiverUser)
            ->post(route('caregiver.messages.store'), [
                'elderly_id' => $otherElderlyProfile->id,
                'message' => 'This should fail.',
            ]);

        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('care_messages', [
            'elderly_id' => $otherElderlyProfile->id,
            'message' => 'This should fail.',
        ]);
    }

    public function test_elderly_can_send_message_to_linked_caregiver(): void
    {
        [, $caregiverProfile, $elderlyUser, $elderlyProfile] = $this->createLinkedProfiles();

        $response = $this->actingAs($elderlyUser)
            ->post(route('elderly.messages.store'), [
                'message' => 'I already took my medicine.',
            ]);

        $response->assertRedirect(route('elderly.messages.index'));

        $this->assertDatabaseHas('care_messages', [
            'caregiver_id' => $caregiverProfile->id,
            'elderly_id' => $elderlyProfile->id,
            'sender_profile_id' => $elderlyProfile->id,
            'message' => 'I already took my medicine.',
        ]);
    }

    public function test_messaging_routes_gracefully_handle_missing_table(): void
    {
        [$caregiverUser, , , $elderlyProfile] = $this->createLinkedProfiles();

        Schema::dropIfExists('care_messages');

        $getResponse = $this->actingAs($caregiverUser)
            ->get(route('caregiver.messages.index', ['elderly' => $elderlyProfile->id]));

        $getResponse->assertRedirect(route('caregiver.dashboard'));
        $getResponse->assertSessionHas('error');

        $postResponse = $this->actingAs($caregiverUser)
            ->post(route('caregiver.messages.store'), [
                'elderly_id' => $elderlyProfile->id,
                'message' => 'Will not send while migration is missing.',
            ]);

        $postResponse->assertSessionHas('error');
    }
}
