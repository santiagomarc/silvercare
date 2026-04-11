<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SosTest extends TestCase
{
    use RefreshDatabase;

    private function createLinkedPair(): array
    {
        $caregiver = User::factory()->create();
        $caregiverProfile = UserProfile::create([
            'user_id' => $caregiver->id,
            'user_type' => 'caregiver',
            'profile_completed' => true,
        ]);

        $elderly = User::factory()->create();
        $elderlyProfile = UserProfile::create([
            'user_id' => $elderly->id,
            'user_type' => 'elderly',
            'profile_completed' => true,
            'caregiver_id' => $caregiverProfile->id,
        ]);

        return [$elderly, $elderlyProfile, $caregiver, $caregiverProfile];
    }

    public function test_sos_creates_notification_when_caregiver_linked(): void
    {
        [$elderly, $elderlyProfile, $caregiver, $caregiverProfile] = $this->createLinkedPair();

        $response = $this->actingAs($elderly)->postJson('/sos');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('notifications', [
            'elderly_id' => $elderlyProfile->id,
            'type' => 'sos_alert',
        ]);
    }

    public function test_sos_fails_when_no_caregiver_linked(): void
    {
        $elderly = User::factory()->create();
        UserProfile::create([
            'user_id' => $elderly->id,
            'user_type' => 'elderly',
            'profile_completed' => true,
            'caregiver_id' => null,
        ]);

        $response = $this->actingAs($elderly)->postJson('/sos');

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_sos_requires_authentication(): void
    {
        $response = $this->postJson('/sos');
        $response->assertStatus(401);
    }
}
