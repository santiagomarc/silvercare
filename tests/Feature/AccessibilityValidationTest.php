<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessibilityValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createElderlyUser(): array
    {
        $user = User::factory()->create(['name' => 'Alice Senior']);
        $profile = UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'elderly',
            'username' => 'alicesenior',
            'profile_completed' => true,
        ]);

        return [$user, $profile];
    }

    public function test_elderly_dashboard_renders_accessible_components(): void
    {
        [$user, $profile] = $this->createElderlyUser();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        // Check for accessible voice assistant button
        $response->assertSee('id="senior-voice-mic-btn"', false);
        $response->assertSee('title="Speak a vital reading', false);
        // Check for wellness check-in section
        $response->assertSee('Daily Wellness Check-in', false);
    }
}
