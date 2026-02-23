<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Services\AiAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaregiverAiController extends Controller
{
    protected AiAssistantService $aiService;

    public function __construct(AiAssistantService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Handle a caregiver AI analysis request (non-streaming fallback).
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'session_id' => 'nullable|integer',
        ]);

        try {
            $caregiver = Auth::user();
            $elderlyProfileId = $caregiver->profile->id ?? null;

            if (!$elderlyProfileId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No patient linked to your account.',
                ], 400);
            }

            // Resolve the elderly user linked to this caregiver
            $elderlyProfileId = $this->getLinkedElderlyId($caregiver);

            if (!$elderlyProfileId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No patient is currently linked to your account.',
                ], 400);
            }

            $session = $this->resolveSession($caregiver, $request->input('session_id'));
            $response = $this->aiService->analyzePatientHealth($caregiver, $elderlyProfileId, $request->message);

            // Persist messages
            $session->messages()->create(['role' => 'user', 'content' => $request->message]);
            $session->messages()->create(['role' => 'model', 'content' => $response]);

            return response()->json([
                'success' => true,
                'message' => $response,
                'session_id' => $session->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Caregiver AI Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "I'm sorry, I couldn't analyze the data right now. Please try again.",
            ], 500);
        }
    }

    /**
     * Stream caregiver AI analysis via Server-Sent Events.
     */
    public function stream(Request $request): StreamedResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'session_id' => 'nullable|integer',
        ]);

        $caregiver = Auth::user();
        $elderlyProfileId = $this->getLinkedElderlyId($caregiver);
        $session = $this->resolveSession($caregiver, $request->input('session_id'));
        $question = $request->input('message');

        return new StreamedResponse(function () use ($caregiver, $elderlyProfileId, $question, $session) {
            echo "data: " . json_encode(['type' => 'session', 'session_id' => $session->id]) . "\n\n";
            ob_flush();
            flush();

            if (!$elderlyProfileId) {
                echo "data: " . json_encode([
                    'type' => 'error',
                    'content' => 'No patient is currently linked to your account.',
                ]) . "\n\n";
                ob_flush();
                flush();
                return;
            }

            try {
                $generator = $this->aiService->analyzePatientHealthStream(
                    $caregiver, $elderlyProfileId, $question, $session
                );

                foreach ($generator as $chunk) {
                    echo "data: " . json_encode(['type' => 'chunk', 'content' => $chunk]) . "\n\n";
                    ob_flush();
                    flush();
                }

                echo "data: " . json_encode(['type' => 'done']) . "\n\n";
                ob_flush();
                flush();
            } catch (\Exception $e) {
                Log::error('Caregiver AI Stream Error: ' . $e->getMessage());
                echo "data: " . json_encode([
                    'type' => 'error',
                    'content' => "I'm sorry, analysis failed. Please try again.",
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
     * Get chat history for the caregiver AI session.
     */
    public function history(Request $request)
    {
        $caregiver = Auth::user();
        $session = $this->resolveSession($caregiver, $request->input('session_id'));

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'messages' => $this->aiService->getChatHistory($session),
            'suggested_prompts' => AiAssistantService::CAREGIVER_SUGGESTED_PROMPTS,
        ]);
    }

    /**
     * Start a new chat session.
     */
    public function newSession()
    {
        $caregiver = Auth::user();
        $session = $caregiver->chatSessions()->create([
            'title' => 'Analysis ' . now()->format('M j, Y g:i A'),
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
        ]);
    }

    /**
     * Get the elderly profile ID linked to this caregiver.
     */
    protected function getLinkedElderlyId($caregiver): ?int
    {
        $caregiverProfile = $caregiver->profile;
        if (!$caregiverProfile) return null;

        // Find elderly users whose profile lists this caregiver
        $elderlyProfile = \App\Models\UserProfile::where('caregiver_id', $caregiverProfile->id)
            ->where('user_type', 'elderly')
            ->first();

        return $elderlyProfile?->id;
    }

    /**
     * Resolve the chat session.
     */
    protected function resolveSession($user, ?int $sessionId): ChatSession
    {
        if ($sessionId) {
            $session = ChatSession::where('id', $sessionId)
                ->where('user_id', $user->id)
                ->first();
            if ($session) return $session;
        }

        return $user->activeChatSession();
    }
}
