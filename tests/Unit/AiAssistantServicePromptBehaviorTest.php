<?php

namespace Tests\Unit;

use App\Models\ChatSession;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AiAssistantService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class AiAssistantServicePromptBehaviorTest extends TestCase
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

    private function makeService(): AiAssistantService
    {
        return new AiAssistantService(new NotificationService());
    }

    private function invokeProtected(AiAssistantService $service, string $method, array $arguments = []): mixed
    {
        $refMethod = new ReflectionMethod($service, $method);
        $refMethod->setAccessible(true);

        return $refMethod->invokeArgs($service, $arguments);
    }

    public function test_prompt_skips_duty_context_for_casual_messages(): void
    {
        $user = $this->createElderlyUser();
        $session = $user->chatSessions()->create(['title' => 'Chat']);

        $service = $this->makeService();
        $prompt = $this->invokeProtected($service, 'buildElderlySystemPrompt', [$user, 'i am bored what should i do?', $session]);

        $this->assertStringContainsString('No health, medication, task, or vitals snapshot is injected for this turn', $prompt);
        $this->assertStringNotContainsString("TODAY'S MEDICATIONS:", $prompt);
    }

    public function test_prompt_includes_duty_context_for_explicit_health_requests(): void
    {
        $user = $this->createElderlyUser();
        $session = $user->chatSessions()->create(['title' => 'Chat']);

        $service = $this->makeService();
        $prompt = $this->invokeProtected($service, 'buildElderlySystemPrompt', [$user, 'what medications do i have today?', $session]);

        $this->assertStringContainsString("TODAY'S MEDICATIONS:", $prompt);
        $this->assertStringContainsString('HEALTH AND DAILY DATA', $prompt);
    }

    public function test_action_tool_guard_requires_clear_intent_and_context(): void
    {
        $service = $this->makeService();

        $this->assertFalse(
            $this->invokeProtected($service, 'shouldAllowActionTool', ['mark_task_complete', 'i am done with this weather'])
        );

        $this->assertTrue(
            $this->invokeProtected($service, 'shouldAllowActionTool', ['mark_task_complete', 'please mark this task complete'])
        );

        $this->assertTrue(
            $this->invokeProtected($service, 'shouldAllowActionTool', ['log_medication', 'i just took my medication, please log it'])
        );
    }

    public function test_conversation_history_labels_assistant_as_silvia(): void
    {
        $user = $this->createElderlyUser();
        $session = $user->chatSessions()->create(['title' => 'Chat']);

        $session->messages()->create([
            'role' => 'user',
            'content' => 'hello',
        ]);

        $session->messages()->create([
            'role' => 'model',
            'content' => 'hi there',
        ]);

        $service = $this->makeService();
        $history = $this->invokeProtected($service, 'buildConversationHistory', [$session]);

        $this->assertStringContainsString('Silvia: hi there', $history);
        $this->assertMatchesRegularExpression('/\[[0-9]{2}:[0-9]{2}\]/', $history);
    }
}
