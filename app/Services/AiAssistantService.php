<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Checklist;
use App\Models\HealthMetric;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\User;
use Carbon\Carbon;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class AiAssistantService
{
    protected NotificationService $notificationService;

    /**
     * Emergency keywords that trigger caregiver alerts.
     */
    private const EMERGENCY_KEYWORDS = [
        'chest pain', 'heart attack', 'stroke', 'can\'t breathe', 'cannot breathe',
        'difficulty breathing', 'fallen', 'fell down', 'i fell', 'severe pain',
        'unconscious', 'bleeding', 'emergency', 'help me', 'dizzy', 'fainted',
        'blurry vision', 'numbness', 'choking',
    ];

    /**
     * Suggested prompts for elderly users.
     */
    public const ELDERLY_SUGGESTED_PROMPTS = [
        '💊 What medications do I have today?',
        '📋 What tasks should I do today?',
        '❤️ How is my health this week?',
        '🌤️ Give me a wellness tip',
    ];

    /**
     * Suggested prompts for caregiver users.
     */
    public const CAREGIVER_SUGGESTED_PROMPTS = [
        '📊 Summarize my patient\'s health this week',
        '💊 Any missed medications recently?',
        '📈 Are there any concerning health trends?',
        '📋 What tasks are pending for my patient?',
    ];

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // =========================================================================
    // CHAT (Elderly User)
    // =========================================================================

    /**
     * Process a chat message from an elderly user.
     * Returns the AI response string.
     */
    public function chat(User $user, string $userMessage, ChatSession $session): string
    {
        // 1. Persist user message
        $session->messages()->create([
            'role' => 'user',
            'content' => $userMessage,
        ]);

        // 2. Check for emergency keywords — fire notification in background
        $this->detectEmergency($user, $userMessage);

        // 3. Build context
        $systemPrompt = $this->buildElderlySystemPrompt($user);
        $conversationHistory = $this->buildConversationHistory($session);

        // 4. Call Gemini
        $aiResponse = $this->callGemini($systemPrompt, $conversationHistory, $userMessage);

        // 5. Check if the AI response contains an action request
        $aiResponse = $this->processActions($user, $aiResponse);

        // 6. Persist AI response
        $session->messages()->create([
            'role' => 'model',
            'content' => $aiResponse,
        ]);

        // 7. Auto-title session on first exchange
        if ($session->messages()->count() <= 2 && !$session->title) {
            $session->update(['title' => \Illuminate\Support\Str::limit($userMessage, 50)]);
        }

        return $aiResponse;
    }

    /**
     * Stream a chat message via SSE for real-time token delivery.
     */
    public function chatStream(User $user, string $userMessage, ChatSession $session): \Generator
    {
        // 1. Persist user message
        $session->messages()->create([
            'role' => 'user',
            'content' => $userMessage,
        ]);

        // 2. Check for emergency keywords
        $this->detectEmergency($user, $userMessage);

        // 3. Build context
        $systemPrompt = $this->buildElderlySystemPrompt($user);
        $conversationHistory = $this->buildConversationHistory($session);

        // 4. Stream from Gemini
        $fullResponse = '';

        try {
            $stream = Gemini::generativeModel('gemini-2.5-flash')
                ->streamGenerateContent($systemPrompt . "\n\n" . $conversationHistory . "\nUser: " . $userMessage);

            foreach ($stream as $response) {
                $chunk = $response->text();
                $fullResponse .= $chunk;
                yield $chunk;
            }
        } catch (\Exception $e) {
            Log::error('Gemini Stream Error: ' . $e->getMessage());
            $fallback = "I'm sorry, I'm having a little trouble right now. Please try again in a moment.";
            $fullResponse = $fallback;
            yield $fallback;
        }

        // 5. Process actions in the completed response
        $fullResponse = $this->processActions($user, $fullResponse);

        // 6. Persist the full AI response
        $session->messages()->create([
            'role' => 'model',
            'content' => $fullResponse,
        ]);

        // 7. Auto-title
        if ($session->messages()->count() <= 2 && !$session->title) {
            $session->update(['title' => \Illuminate\Support\Str::limit($userMessage, 50)]);
        }
    }

    // =========================================================================
    // CAREGIVER AI ANALYZER
    // =========================================================================

    /**
     * Analyze a patient's health data and return an AI-generated summary.
     */
    public function analyzePatientHealth(User $caregiver, int $elderlyProfileId, string $question = ''): string
    {
        $systemPrompt = $this->buildCaregiverSystemPrompt($caregiver, $elderlyProfileId);

        $prompt = $question ?: 'Give me a comprehensive health summary of my patient for the past week. Include medication adherence, vital signs trends, task completion, and any areas of concern.';

        return $this->callGemini($systemPrompt, '', $prompt);
    }

    /**
     * Stream caregiver analysis via SSE.
     */
    public function analyzePatientHealthStream(User $caregiver, int $elderlyProfileId, string $question, ChatSession $session): \Generator
    {
        // 1. Persist user message
        $session->messages()->create([
            'role' => 'user',
            'content' => $question,
        ]);

        $systemPrompt = $this->buildCaregiverSystemPrompt($caregiver, $elderlyProfileId);
        $conversationHistory = $this->buildConversationHistory($session);

        $fullResponse = '';

        try {
            $stream = Gemini::generativeModel('gemini-2.5-flash')
                ->streamGenerateContent($systemPrompt . "\n\n" . $conversationHistory . "\nCaregiver: " . $question);

            foreach ($stream as $response) {
                $chunk = $response->text();
                $fullResponse .= $chunk;
                yield $chunk;
            }
        } catch (\Exception $e) {
            Log::error('Gemini Stream Error (Caregiver): ' . $e->getMessage());
            $fallback = "I'm sorry, I couldn't analyze the data right now. Please try again.";
            $fullResponse = $fallback;
            yield $fallback;
        }

        // Persist AI response
        $session->messages()->create([
            'role' => 'model',
            'content' => $fullResponse,
        ]);
    }

    // =========================================================================
    // SYSTEM PROMPT BUILDERS
    // =========================================================================

    /**
     * Build the system prompt for elderly user conversations.
     */
    protected function buildElderlySystemPrompt(User $user): string
    {
        $today = Carbon::now();
        $dayName = $today->format('l');
        $dateFormatted = $today->format('F j, Y');
        $profile = $user->profile;

        // Gather medications for today
        $medications = Medication::where('elderly_id', $profile->id)
            ->where('is_active', true)
            ->where(function ($q) use ($dayName) {
                $q->whereJsonContains('days_of_week', $dayName)
                  ->orWhereNull('days_of_week');
            })
            ->get()
            ->map(function ($med) use ($profile, $today) {
                $timesStr = implode(', ', $med->times_of_day ?? []);

                // Check which doses are taken today
                $takenLogs = MedicationLog::where('elderly_id', $profile->id)
                    ->where('medication_id', $med->id)
                    ->whereDate('scheduled_time', $today)
                    ->where('is_taken', true)
                    ->pluck('scheduled_time')
                    ->map(fn($t) => Carbon::parse($t)->format('H:i'))
                    ->toArray();

                $takenStr = count($takenLogs) > 0 ? ' [TAKEN: ' . implode(', ', $takenLogs) . ']' : ' [NOT YET TAKEN]';
                return "• {$med->name} ({$med->dosage}) — scheduled at {$timesStr}{$takenStr}";
            })->implode("\n");

        // Gather tasks for today
        $tasks = Checklist::where('elderly_id', $profile->id)
            ->whereDate('due_date', $today)
            ->get()
            ->map(function ($task) {
                $status = $task->is_completed ? '✅ Done' : '⬜ Pending';
                $priority = $task->priority ? " [{$task->priority}]" : '';
                return "• {$task->task} — {$status}{$priority}";
            })->implode("\n");

        // Gather latest vitals (last 24h)
        $latestVitals = HealthMetric::where('elderly_id', $profile->id)
            ->where('measured_at', '>=', $today->copy()->subDay())
            ->orderBy('measured_at', 'desc')
            ->get()
            ->groupBy('type')
            ->map(function ($records, $type) {
                $latest = $records->first();
                $val = $latest->value_text ?? $latest->value;
                $unit = $latest->unit ? " {$latest->unit}" : '';
                return "• {$type}: {$val}{$unit} (at " . Carbon::parse($latest->measured_at)->format('g:i A') . ")";
            })->implode("\n");

        // Medical profile info
        $medicalInfo = '';
        if ($profile) {
            $conditions = $profile->medical_conditions ?? 'None noted';
            $allergies = $profile->allergies ?? 'None noted';
            $medicalInfo = "Medical conditions: {$conditions}\nAllergies: {$allergies}";
        }

        return <<<PROMPT
You are the SilverCare AI Assistant — a warm, empathetic, and patient companion built for elderly users.

TODAY: {$dayName}, {$dateFormatted}
USER: {$user->name}
{$medicalInfo}

=== TODAY'S MEDICATIONS ===
{$medications}

=== TODAY'S TASKS ===
{$tasks}

=== RECENT VITAL SIGNS (last 24h) ===
{$latestVitals}

=== INSTRUCTIONS ===
1. Be conversational, warm, and encouraging. Use simple language.
2. When the user asks about their medications or tasks, reference the real data above.
3. You can format responses using **bold**, bullet points, and line breaks for readability.
4. NEVER provide medical diagnoses. If the user describes serious symptoms, strongly recommend they contact their caregiver or doctor immediately.
5. If the user seems distressed or mentions an emergency, respond with care and urgency.
6. If the user asks you to mark a task as done, include the special tag [ACTION:COMPLETE_TASK:task_id] in your response (replace task_id with the actual ID).
7. If the user asks you to log a medication as taken, include the special tag [ACTION:LOG_MEDICATION:medication_id] in your response.
8. Keep answers concise (2-4 paragraphs max) unless the user asks for detail.
PROMPT;
    }

    /**
     * Build the system prompt for caregiver AI analysis.
     */
    protected function buildCaregiverSystemPrompt(User $caregiver, int $elderlyProfileId): string
    {
        $today = Carbon::now();
        $weekAgo = $today->copy()->subDays(7);

        // Get the elderly user's profile
        $elderlyProfile = \App\Models\UserProfile::find($elderlyProfileId);
        $elderlyUser = $elderlyProfile ? $elderlyProfile->user : null;
        $elderlyName = $elderlyUser ? $elderlyUser->name : 'Patient';

        // Medication adherence for the past week
        $totalScheduled = MedicationLog::where('elderly_id', $elderlyProfileId)
            ->whereBetween('scheduled_time', [$weekAgo, $today])
            ->count();
        $totalTaken = MedicationLog::where('elderly_id', $elderlyProfileId)
            ->whereBetween('scheduled_time', [$weekAgo, $today])
            ->where('is_taken', true)
            ->count();
        $adherenceRate = $totalScheduled > 0 ? round(($totalTaken / $totalScheduled) * 100, 1) : 0;

        $missedMeds = MedicationLog::where('elderly_id', $elderlyProfileId)
            ->whereBetween('scheduled_time', [$weekAgo, $today])
            ->where('is_taken', false)
            ->with('medication')
            ->get()
            ->map(function ($log) {
                $medName = $log->medication->name ?? 'Unknown';
                $time = Carbon::parse($log->scheduled_time)->format('M j, g:i A');
                return "• {$medName} — missed at {$time}";
            })->implode("\n");

        // Health metrics summary (last 7 days)
        $healthMetrics = HealthMetric::where('elderly_id', $elderlyProfileId)
            ->whereBetween('measured_at', [$weekAgo, $today])
            ->orderBy('measured_at', 'desc')
            ->get()
            ->groupBy('type')
            ->map(function ($records, $type) {
                $count = $records->count();
                $avg = round($records->avg('value'), 1);
                $min = $records->min('value');
                $max = $records->max('value');
                $latest = $records->first();
                $latestVal = $latest->value_text ?? $latest->value;
                $unit = $latest->unit ?? '';
                return "• {$type}: {$count} readings | Avg: {$avg} | Range: {$min}-{$max} | Latest: {$latestVal} {$unit}";
            })->implode("\n");

        // Task completion for the past week
        $totalTasks = Checklist::where('elderly_id', $elderlyProfileId)
            ->whereBetween('due_date', [$weekAgo, $today])
            ->count();
        $completedTasks = Checklist::where('elderly_id', $elderlyProfileId)
            ->whereBetween('due_date', [$weekAgo, $today])
            ->where('is_completed', true)
            ->count();
        $taskRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

        return <<<PROMPT
You are the SilverCare Caregiver AI Analyst — a clinical, data-driven assistant for caregivers monitoring their elderly patient.

CAREGIVER: {$caregiver->name}
PATIENT: {$elderlyName}
ANALYSIS PERIOD: {$weekAgo->format('M j')} – {$today->format('M j, Y')}

=== MEDICATION ADHERENCE (7 days) ===
Adherence Rate: {$adherenceRate}% ({$totalTaken}/{$totalScheduled} doses taken)
Missed Doses:
{$missedMeds}

=== HEALTH METRICS (7 days) ===
{$healthMetrics}

=== TASK COMPLETION (7 days) ===
Completion Rate: {$taskRate}% ({$completedTasks}/{$totalTasks} tasks)

=== INSTRUCTIONS ===
1. Provide clinical, factual analysis based on the real data above.
2. Highlight any concerning trends (declining adherence, abnormal vitals, etc.).
3. Use **bold** for emphasis, bullet points for clarity, and keep insights actionable.
4. NEVER make medical diagnoses — suggest the caregiver consult a physician if metrics are concerning.
5. If asked about specific metrics, reference exact numbers and dates.
6. Be professional but compassionate — the caregiver cares about their patient.
PROMPT;
    }

    // =========================================================================
    // CONVERSATION HISTORY
    // =========================================================================

    /**
     * Build conversation history string from stored messages.
     */
    protected function buildConversationHistory(ChatSession $session): string
    {
        $messages = $session->recentMessages(20);

        if ($messages->isEmpty()) {
            return '';
        }

        return $messages->map(function (ChatMessage $msg) {
            $role = $msg->role === 'user' ? 'User' : 'Assistant';
            return "{$role}: {$msg->content}";
        })->implode("\n\n");
    }

    // =========================================================================
    // GEMINI API CALL
    // =========================================================================

    /**
     * Call the Gemini API with the given prompt components.
     */
    protected function callGemini(string $systemPrompt, string $history, string $userMessage): string
    {
        try {
            $fullPrompt = $systemPrompt;

            if ($history) {
                $fullPrompt .= "\n\n=== CONVERSATION HISTORY ===\n" . $history;
            }

            $fullPrompt .= "\n\nUser: " . $userMessage;

            $result = Gemini::generativeModel('gemini-2.5-flash')
                ->generateContent($fullPrompt);

            return $result->text();
        } catch (\Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            return "I'm sorry, I'm having a little trouble connecting right now. Please try again in a moment.";
        }
    }

    // =========================================================================
    // ACTION PROCESSING (Function Calling)
    // =========================================================================

    /**
     * Detect and execute action tags in the AI response.
     * Tags look like: [ACTION:COMPLETE_TASK:123] or [ACTION:LOG_MEDICATION:456]
     */
    protected function processActions(User $user, string $response): string
    {
        $profile = $user->profile;
        if (!$profile) return $response;

        // Process COMPLETE_TASK actions
        if (preg_match_all('/\[ACTION:COMPLETE_TASK:(\d+)\]/', $response, $matches)) {
            foreach ($matches[1] as $taskId) {
                try {
                    $task = Checklist::where('id', $taskId)
                        ->where('elderly_id', $profile->id)
                        ->first();

                    if ($task && !$task->is_completed) {
                        $task->update([
                            'is_completed' => true,
                            'completed_at' => Carbon::now(),
                        ]);
                        // Remove the action tag and add confirmation
                        $response = str_replace(
                            "[ACTION:COMPLETE_TASK:{$taskId}]",
                            "✅ *Task \"{$task->task}\" marked as done!*",
                            $response
                        );
                    } else {
                        $response = str_replace("[ACTION:COMPLETE_TASK:{$taskId}]", '', $response);
                    }
                } catch (\Exception $e) {
                    Log::warning("AI action failed (COMPLETE_TASK:{$taskId}): " . $e->getMessage());
                    $response = str_replace("[ACTION:COMPLETE_TASK:{$taskId}]", '', $response);
                }
            }
        }

        // Process LOG_MEDICATION actions
        if (preg_match_all('/\[ACTION:LOG_MEDICATION:(\d+)\]/', $response, $matches)) {
            foreach ($matches[1] as $medId) {
                try {
                    $medication = Medication::where('id', $medId)
                        ->where('elderly_id', $profile->id)
                        ->first();

                    if ($medication) {
                        MedicationLog::create([
                            'elderly_id' => $profile->id,
                            'medication_id' => $medication->id,
                            'scheduled_time' => Carbon::now(),
                            'is_taken' => true,
                            'taken_at' => Carbon::now(),
                        ]);

                        $response = str_replace(
                            "[ACTION:LOG_MEDICATION:{$medId}]",
                            "💊 *{$medication->name} logged as taken!*",
                            $response
                        );
                    } else {
                        $response = str_replace("[ACTION:LOG_MEDICATION:{$medId}]", '', $response);
                    }
                } catch (\Exception $e) {
                    Log::warning("AI action failed (LOG_MEDICATION:{$medId}): " . $e->getMessage());
                    $response = str_replace("[ACTION:LOG_MEDICATION:{$medId}]", '', $response);
                }
            }
        }

        return $response;
    }

    // =========================================================================
    // EMERGENCY DETECTION
    // =========================================================================

    /**
     * Detect emergency keywords in user messages and alert the caregiver.
     */
    protected function detectEmergency(User $user, string $message): void
    {
        $lowerMessage = strtolower($message);

        foreach (self::EMERGENCY_KEYWORDS as $keyword) {
            if (str_contains($lowerMessage, $keyword)) {
                $this->fireEmergencyAlert($user, $message, $keyword);
                return; // Only one alert per message
            }
        }
    }

    /**
     * Fire a high-priority emergency notification to the caregiver.
     */
    protected function fireEmergencyAlert(User $user, string $message, string $matchedKeyword): void
    {
        try {
            $profile = $user->profile;
            if (!$profile) return;

            // Find the caregiver linked to this elderly user
            $caregiverId = $profile->caregiver_id;
            if (!$caregiverId) return;

            $this->notificationService->createNotification([
                'elderly_id' => $profile->id,
                'type' => 'emergency_alert',
                'title' => '🚨 Emergency Alert from ' . $user->name,
                'message' => "{$user->name} may need urgent help. They mentioned: \"{$message}\" (detected: {$matchedKeyword})",
                'severity' => 'critical',
                'metadata' => json_encode([
                    'source' => 'ai_chat',
                    'keyword' => $matchedKeyword,
                    'original_message' => $message,
                    'timestamp' => now()->toIso8601String(),
                ]),
            ]);

            Log::warning("Emergency alert fired for user {$user->id}: keyword '{$matchedKeyword}'");
        } catch (\Exception $e) {
            Log::error("Failed to fire emergency alert: " . $e->getMessage());
        }
    }

    // =========================================================================
    // CHAT HISTORY
    // =========================================================================

    /**
     * Get the chat history for a session (for loading on frontend).
     */
    public function getChatHistory(ChatSession $session): array
    {
        return $session->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn(ChatMessage $msg) => [
                'role' => $msg->role === 'user' ? 'user' : 'ai',
                'content' => $msg->content,
                'time' => $msg->created_at->format('g:i A'),
            ])
            ->toArray();
    }

    /**
     * Get all chat sessions for a user (for session list).
     */
    public function getUserSessions(User $user): array
    {
        return $user->chatSessions()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn(ChatSession $s) => [
                'id' => $s->id,
                'title' => $s->title ?? 'Chat ' . $s->created_at->format('M j'),
                'date' => $s->created_at->format('M j, Y'),
                'message_count' => $s->messages()->count(),
            ])
            ->toArray();
    }
}
