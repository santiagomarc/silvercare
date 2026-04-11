<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAssistantSessionContinuityTest extends TestCase
{
    use RefreshDatabase;

    private function createElderlyUser(): User
    {
        $user = User::factory()->create();

        UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'elderly',
            'profile_completed' => true,
            'profile_skipped' => false,
        ]);

        return $user->fresh();
    }

    public function test_active_chat_session_reuses_latest_existing_session(): void
    {
        $user = $this->createElderlyUser();

        $older = $user->chatSessions()->create(['title' => 'Older Session']);
        $older->forceFill([
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ])->save();

        $latest = $user->chatSessions()->create(['title' => 'Latest Session']);

        $active = $user->fresh()->activeChatSession();

        $this->assertSame($latest->id, $active->id);
        $this->assertDatabaseCount('chat_sessions', 2);
    }

    public function test_history_endpoint_without_session_id_returns_latest_session(): void
    {
        $user = $this->createElderlyUser();

        $older = $user->chatSessions()->create(['title' => 'Older Session']);
        ChatMessage::create([
            'chat_session_id' => $older->id,
            'role' => 'user',
            'content' => 'old message',
        ]);

        $latest = $user->chatSessions()->create(['title' => 'Latest Session']);
        ChatMessage::create([
            'chat_session_id' => $latest->id,
            'role' => 'user',
            'content' => 'latest message',
        ]);

        $response = $this->actingAs($user)->getJson(route('elderly.ai-assistant.history'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('session_id', $latest->id)
            ->assertJsonPath('messages.0.content', 'latest message');
    }
}
