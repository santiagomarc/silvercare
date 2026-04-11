<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAiSession;
use App\Http\Requests\AiChatRequest;
use App\Services\AiAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiAssistantController extends Controller
{
    use ResolvesAiSession;

    protected AiAssistantService $aiService;

    public function __construct(AiAssistantService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Show the AI assistant page (optional standalone view).
     */
    public function index()
    {
        return view('elderly.dashboard'); // Widget is on the dashboard
    }

    /**
     * Handle chat requests — non-streaming fallback.
     */
    public function chat(AiChatRequest $request)
    {
        try {
            $user = Auth::user();
            $session = $this->resolveSession($user, $request->input('session_id'));
            $response = $this->aiService->chat($user, $request->message, $session);

            return response()->json([
                'success' => true,
                'message' => $response,
                'session_id' => $session->id,
                'actions' => $this->aiService->consumeActionEvents(),
            ]);
        } catch (\Exception $e) {
            Log::error('AI Chat Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "I'm sorry, I'm having a little trouble connecting right now. Please try again in a moment.",
            ], 500);
        }
    }

    /**
     * Stream chat response via Server-Sent Events (SSE).
     */
    public function stream(AiChatRequest $request): StreamedResponse
    {
        $user = Auth::user();
        $session = $this->resolveSession($user, $request->input('session_id'));
        $userMessage = $request->input('message');

        return new StreamedResponse(function () use ($user, $userMessage, $session) {
            // Send the session_id first so the frontend can track it
            echo "data: " . json_encode(['type' => 'session', 'session_id' => $session->id]) . "\n\n";
            ob_flush();
            flush();

            try {
                $generator = $this->aiService->chatStream($user, $userMessage, $session);

                foreach ($generator as $payload) {
                    if (is_array($payload) && ($payload['type'] ?? null) === 'action') {
                        echo "data: " . json_encode([
                            'type' => 'action',
                            'action' => $payload['action'] ?? null,
                        ]) . "\n\n";
                        ob_flush();
                        flush();
                        continue;
                    }

                    $content = is_array($payload)
                        ? ($payload['content'] ?? '')
                        : (string) $payload;

                    if ($content === '') {
                        continue;
                    }

                    echo "data: " . json_encode(['type' => 'chunk', 'content' => $content]) . "\n\n";
                    ob_flush();
                    flush();
                }

                echo "data: " . json_encode(['type' => 'done']) . "\n\n";
                ob_flush();
                flush();
            } catch (\Exception $e) {
                Log::error('AI Stream Error: ' . $e->getMessage());
                echo "data: " . json_encode([
                    'type' => 'error',
                    'content' => "I'm sorry, I'm having a little trouble right now. Please try again.",
                ]) . "\n\n";
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Get chat history for the current session.
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        $session = $this->resolveSession($user, $request->input('session_id'));

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'messages' => $this->aiService->getChatHistory($session),
            'suggested_prompts' => AiAssistantService::ELDERLY_SUGGESTED_PROMPTS,
        ]);
    }

    /**
     * List all chat sessions for the user.
     */
    public function sessions()
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'sessions' => $this->aiService->getUserSessions($user),
        ]);
    }

    /**
     * Start a new chat session.
     */
    public function newSession()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $session = $user->chatSessions()->create([
            'title' => 'Chat ' . now()->format('M j, Y g:i A'),
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
        ]);
    }

}
