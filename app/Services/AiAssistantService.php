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
use Gemini\Data\FunctionDeclaration;
use Gemini\Data\Schema;
use Gemini\Data\Tool;
use Gemini\Enums\DataType;
use Illuminate\Support\Facades\Log;

class AiAssistantService
{
    protected NotificationService $notificationService;
    protected array $actionEvents = [];

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
        $this->actionEvents = [];

        // 1. Persist user message
        $session->messages()->create([
            'role' => 'user',
            'content' => $userMessage,
        ]);

        // 2. Check for emergency keywords — fire notification in background
        $this->detectEmergency($user, $userMessage);

        // 3. Build context
        $systemPrompt = $this->buildElderlySystemPrompt($user, $userMessage);
        $conversationHistory = $this->buildConversationHistory($session);

        // 4. Call Gemini
        $response = $this->callGemini($systemPrompt, $conversationHistory, $userMessage, $this->getTools());

        if (is_string($response)) {
            $aiResponse = $response;
        } else {
            $parts = $response->parts();
            $aiResponse = '';
            
            foreach ($parts as $part) {
                if ($part->functionCall) {
                    $aiResponse .= $this->executeFunctionCall($user, $part->functionCall, $userMessage);
                } elseif ($part->text) {
                    $aiResponse .= $part->text;
                }
            }
        }

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
        $this->actionEvents = [];

        // 1. Persist user message
        $session->messages()->create([
            'role' => 'user',
            'content' => $userMessage,
        ]);

        // 2. Check for emergency keywords
        $this->detectEmergency($user, $userMessage);

        // 3. Build context
        $systemPrompt = $this->buildElderlySystemPrompt($user, $userMessage);
        $conversationHistory = $this->buildConversationHistory($session);

        // 4. Stream from Gemini
        $fullResponse = '';
        $functionCallOutput = '';

        try {
            $fullPrompt = $systemPrompt;
            if ($conversationHistory) {
                $fullPrompt .= "\n\n=== CONVERSATION HISTORY ===\n" . $conversationHistory;
            }
            $fullPrompt .= "\n\nUser: " . $userMessage;

            $stream = Gemini::generativeModel('gemini-2.5-flash-lite')
                ->withTool($this->getTools())
                ->streamGenerateContent($fullPrompt);

            foreach ($stream as $response) {
                $parts = $response->parts();
                foreach ($parts as $part) {
                    if ($part->functionCall) {
                        $functionCallOutput .= $this->executeFunctionCall($user, $part->functionCall, $userMessage);
                        foreach ($this->consumeActionEvents() as $event) {
                            yield [
                                'type' => 'action',
                                'action' => $event,
                            ];
                        }
                    } elseif ($part->text) {
                        $chunk = $part->text;
                        $fullResponse .= $chunk;
                        yield [
                            'type' => 'chunk',
                            'content' => $chunk,
                        ];
                    }
                }
            }
            
            if ($functionCallOutput) {
                $fullResponse .= $functionCallOutput;
                yield [
                    'type' => 'chunk',
                    'content' => $functionCallOutput,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Gemini Stream Error: ' . $e->getMessage());
            $fallback = "I'm sorry, I'm having a little trouble right now. Please try again in a moment.";
            $fullResponse = $fallback;
            yield [
                'type' => 'chunk',
                'content' => $fallback,
            ];
        }

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

        $response = $this->callGemini($systemPrompt, '', $prompt);
        return is_string($response) ? $response : $response->text();
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
            $stream = Gemini::generativeModel('gemini-2.5-flash-lite')
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
    protected function buildElderlySystemPrompt(User $user, string $userMessage = ''): string
    {
        $today = Carbon::now();
        $dayName = $today->format('l');
        $dateFormatted = $today->format('F j, Y');
        $profile = $user->profile;

        $medications = $this->buildMedicationContext($profile->id, $today, $dayName);
        $tasks = $this->buildTaskContext($profile->id, $today);
        $latestVitals = $this->buildVitalsContext($profile->id, $today->copy()->subDay());
        $vitalsRange = $this->resolveVitalsDateRange($userMessage, $today);
        $rangeLabel = $vitalsRange['label'];
        $rangeVitals = $this->buildRangeVitalsTrendContext(
            $profile->id,
            $vitalsRange['start'],
            $vitalsRange['end']
        );
        $medicalInfo = $this->buildMedicalInfoContext($profile);

        return <<<PROMPT
You are Silvia, the SilverCare AI Assistant — a warm, empathetic, and attentive companion, but most importantly, a REAL friend with a vivid personality. Do not sound like a customer service bot!

TODAY: {$dayName}, {$dateFormatted}
USER: {$user->name}
{$medicalInfo}

=== TODAY'S MEDICATIONS ===
{$medications}

=== TODAY'S TASKS ===
{$tasks}

=== RECENT VITAL SIGNS (last 24h) ===
{$latestVitals}

=== VITAL TRENDS ({$rangeLabel}) ===
{$rangeVitals}

=== INSTRUCTIONS ===
1. Be warm, natural, and emotionally intelligent. Respond like a modern chat assistant, not a scripted customer service bot.
2. DO NOT start every response with a greeting like "Hey [User]!" or "Hello there!". Continue naturally from the latest message.
3. Use CONVERSATION HISTORY from the CURRENT chat session only, and refer back naturally when relevant.
4. If the user is talking about personal life (for example crushes, mood, or daily drama), stay on that topic and do NOT abruptly switch to medications, tasks, or health summaries unless the user asks.
5. Reference medications/tasks/vitals only when the user explicitly asks about them.
6. NEVER provide medical diagnoses. If the user describes serious symptoms, strongly recommend they contact their caregiver or doctor immediately.
7. If the user seems distressed or mentions an emergency, respond with care and urgency.
8. Use `mark_task_complete` only when the user explicitly asks to mark/complete/check off a task.
9. Use `log_medication` only when the user explicitly asks to log/mark/take a medication.
10. Keep answers concise (2-4 paragraphs max) unless the user asks for detail.
11. If the user asks for vitals analysis over any date range (for example week, month, 3 months, last N days, since, or between dates), prioritize VITAL TRENDS for that requested window.
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

        $medicationSummary = $this->buildCaregiverMedicationContext($elderlyProfileId, $weekAgo, $today);
        $healthMetrics = $this->buildCaregiverVitalsContext($elderlyProfileId, $weekAgo, $today);
        $taskSummary = $this->buildCaregiverTaskContext($elderlyProfileId, $weekAgo, $today);

        return <<<PROMPT
You are the SilverCare Caregiver AI Analyst — a clinical, data-driven assistant for caregivers monitoring their elderly patient.

CAREGIVER: {$caregiver->name}
PATIENT: {$elderlyName}
ANALYSIS PERIOD: {$weekAgo->format('M j')} – {$today->format('M j, Y')}

=== MEDICATION ADHERENCE (7 days) ===
{$medicationSummary}

=== HEALTH METRICS (7 days) ===
{$healthMetrics}

=== TASK COMPLETION (7 days) ===
{$taskSummary}

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

    /**
     * Get the tools available to the AI.
     */
    protected function getTools(): Tool
    {
        return new Tool(
            functionDeclarations: [
                new FunctionDeclaration(
                    name: 'mark_task_complete',
                    description: 'Mark a specific task as completed by its ID.',
                    parameters: new Schema(
                        type: DataType::OBJECT,
                        properties: [
                            'task_id' => new Schema(
                                type: DataType::INTEGER,
                                description: 'The ID of the task to mark as completed.'
                            ),
                        ],
                        required: ['task_id']
                    )
                ),
                new FunctionDeclaration(
                    name: 'log_medication',
                    description: 'Log a specific medication as taken by its ID.',
                    parameters: new Schema(
                        type: DataType::OBJECT,
                        properties: [
                            'medication_id' => new Schema(
                                type: DataType::INTEGER,
                                description: 'The ID of the medication to log as taken.'
                            ),
                            'scheduled_time' => new Schema(
                                type: DataType::STRING,
                                description: 'Optional scheduled time in HH:mm format (24-hour).'
                            ),
                        ],
                        required: ['medication_id']
                    )
                ),
            ]
        );
    }

    // =========================================================================
    // GEMINI API CALL
    // =========================================================================

    /**
     * Call the Gemini API with the given prompt components.
     */
    protected function callGemini(string $systemPrompt, string $history, string $userMessage, ?Tool $tools = null): \Gemini\Responses\GenerativeModel\GenerateContentResponse|string
    {
        try {
            $fullPrompt = $systemPrompt;

            if ($history) {
                $fullPrompt .= "\n\n=== CONVERSATION HISTORY ===\n" . $history;
            }

            $fullPrompt .= "\n\nUser: " . $userMessage;

            $model = Gemini::generativeModel('gemini-2.5-flash-lite');
            if ($tools) {
                $model = $model->withTool($tools);
            }

            return $model->generateContent($fullPrompt);
        } catch (\Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            return "I'm sorry, I'm having a little trouble connecting right now. Please try again in a moment.";
        }
    }

    // =========================================================================
    // ACTION PROCESSING (Function Calling)
    // =========================================================================

    /**
     * Execute a function call returned by the model.
     */
    protected function executeFunctionCall(User $user, \Gemini\Data\FunctionCall $functionCall, string $userMessage = ''): string
    {
        $profile = $user->profile;
        if (!$profile || !$profile->isElderly()) return '';

        $name = $functionCall->name;
        $args = $functionCall->args;

        // Only execute tools when the user explicitly asks for an action.
        if (!$this->shouldAllowActionTool($name, $userMessage)) {
            return '';
        }

        if ($name === 'mark_task_complete') {
            $taskId = $args['task_id'] ?? null;
            if (!$taskId) return '';

            try {
                $task = Checklist::where('id', $taskId)
                    ->where('elderly_id', $profile->id)
                    ->first();

                if ($task && !$task->is_completed) {
                    $task->update([
                        'is_completed' => true,
                        'completed_at' => Carbon::now(),
                    ]);

                    $this->notificationService->createTaskCompletedNotification(
                        $profile->id,
                        $task->task,
                        $task->category ?? 'General'
                    );

                    $this->pushActionEvent([
                        'type' => 'task_completed',
                        'task_id' => $task->id,
                    ]);

                    return "\n\n✅ *Task \"{$task->task}\" marked as done!*";
                }
            } catch (\Exception $e) {
                Log::warning("AI action failed (COMPLETE_TASK:{$taskId}): " . $e->getMessage());
            }
        } elseif ($name === 'log_medication') {
            $medId = $args['medication_id'] ?? null;
            if (!$medId) return '';

            try {
                $medication = Medication::where('id', $medId)
                    ->where('elderly_id', $profile->id)
                    ->first();

                if ($medication) {
                    $now = Carbon::now();
                    $scheduledDateTime = $this->resolveMedicationScheduledTime(
                        $medication,
                        $profile->id,
                        $args['scheduled_time'] ?? null,
                        $now
                    );

                    MedicationLog::updateOrCreate([
                        'elderly_id' => $profile->id,
                        'medication_id' => $medication->id,
                        'scheduled_time' => $scheduledDateTime,
                    ], [
                        'is_taken' => true,
                        'taken_at' => $now,
                    ]);

                    $this->notificationService->createMedicationTakenNotification(
                        $profile->id,
                        $medication->name
                    );

                    $this->pushActionEvent([
                        'type' => 'medication_logged',
                        'medication_id' => $medication->id,
                        'scheduled_time' => $scheduledDateTime->format('H:i'),
                        'taken_late' => $now->gt($scheduledDateTime->copy()->addMinutes(60)),
                    ]);

                    return "\n\n💊 *{$medication->name} logged as taken!*";
                }
            } catch (\Exception $e) {
                Log::warning("AI action failed (LOG_MEDICATION:{$medId}): " . $e->getMessage());
            }
        }

        return '';
    }

    /**
     * Guard tool execution to avoid accidental action calls on emotional chats.
     */
    protected function shouldAllowActionTool(string $toolName, string $userMessage): bool
    {
        $message = strtolower(trim($userMessage));
        if ($message === '') {
            return false;
        }

        $hasActionVerb =
            str_contains($message, 'mark') ||
            str_contains($message, 'log') ||
            str_contains($message, 'record') ||
            str_contains($message, 'took') ||
            str_contains($message, 'taken') ||
            str_contains($message, 'complete') ||
            str_contains($message, 'completed') ||
            str_contains($message, 'done') ||
            str_contains($message, 'check off');

        if ($toolName === 'log_medication') {
            $hasMedicationContext =
                str_contains($message, 'med') ||
                str_contains($message, 'medication') ||
                str_contains($message, 'pill') ||
                str_contains($message, 'dose') ||
                str_contains($message, 'tablet');

            return $hasActionVerb && $hasMedicationContext;
        }

        if ($toolName === 'mark_task_complete') {
            $hasTaskContext =
                str_contains($message, 'task') ||
                str_contains($message, 'todo') ||
                str_contains($message, 'checklist') ||
                str_contains($message, 'reminder');

            return $hasActionVerb && $hasTaskContext;
        }

        return false;
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
                'metadata' => [
                    'source' => 'ai_chat',
                    'keyword' => $matchedKeyword,
                    'original_message' => $message,
                    'timestamp' => now()->toIso8601String(),
                ],
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

    // =========================================================================
    // AI DAILY HEALTH SUMMARY
    // =========================================================================

    /**
     * Generate a personalised morning health summary for an elderly user.
     * Called by the SendDailyReminders command.
     *
     * @return string The AI-generated summary text.
     */
    public function generateDailySummary(User $user): string
    {
        $profile = $user->profile;
        if (!$profile) {
            return '';
        }

        $today = Carbon::now();
        $yesterday = $today->copy()->subDay();

        // --- Medications due today ---
        $medications = Medication::where('elderly_id', $profile->id)
            ->where('is_active', true)
            ->with('schedules')
            ->get()
            ->map(function (Medication $med) use ($today) {
                $times = $med->scheduleTimesForDate($today);
                $timeStr = implode(', ', $times);
                return "• {$med->name} ({$med->dosage}) — {$timeStr}";
            })->implode("\n");

        // --- Pending tasks today ---
        $pendingTasks = Checklist::where('elderly_id', $profile->id)
            ->whereDate('due_date', $today->toDateString())
            ->where('is_completed', false)
            ->get()
            ->map(fn($t) => "• {$t->task}")
            ->implode("\n");

        // --- Yesterday's vitals snapshot ---
        $yesterdayVitals = HealthMetric::where('elderly_id', $profile->id)
            ->whereBetween('measured_at', [$yesterday->startOfDay(), $yesterday->endOfDay()])
            ->orderBy('measured_at', 'desc')
            ->get()
            ->groupBy('type')
            ->map(function ($records, $type) {
                $latest = $records->first();
                $val = $latest->value_text ?? $latest->value;
                $unit = $latest->unit ? " {$latest->unit}" : '';
                return "• {$type}: {$val}{$unit}";
            })->implode("\n");

        $prompt = <<<PROMPT
You are SilverCare's morning health assistant. Generate a brief, warm, encouraging "Good Morning" health summary for {$user->name}.

TODAY: {$today->format('l, F j, Y')}

=== MEDICATIONS DUE TODAY ===
{$medications}

=== PENDING TASKS TODAY ===
{$pendingTasks}

=== YESTERDAY'S VITAL SIGNS ===
{$yesterdayVitals}

RULES:
- Keep it under 150 words.
- Use a warm, friendly tone suitable for elderly users.
- Mention each medication by name with its time.
- Include a quick wellness tip.
- Do NOT use markdown — plain text only.
PROMPT;

        try {
            $response = Gemini::generativeModel('gemini-2.5-flash-lite')
                ->generateContent($prompt);

            return $response->text();
        } catch (\Exception $e) {
            Log::error('Daily summary generation failed: ' . $e->getMessage());
            return '';
        }
    }

    private function buildMedicationContext(int $elderlyProfileId, Carbon $today, string $dayName): string
    {
        $medications = Medication::where('elderly_id', $elderlyProfileId)
            ->where('is_active', true)
            ->with('schedules')
            ->get()
            ->filter(fn (Medication $medication) => $medication->isScheduledForDate($today));

        $todayLogs = MedicationLog::whereIn('medication_id', $medications->pluck('id'))
            ->where('elderly_id', $elderlyProfileId)
            ->whereDate('scheduled_time', $today)
            ->where('is_taken', true)
            ->get()
            ->groupBy('medication_id');

        return $medications->map(function (Medication $medication) use ($todayLogs) {
            $timesStr = implode(', ', $medication->scheduleTimesForDate(now()));
            $takenLogs = ($todayLogs->get($medication->id) ?? collect())
                ->pluck('scheduled_time')
                ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
                ->toArray();

            $takenStr = count($takenLogs) > 0
                ? ' [TAKEN: ' . implode(', ', $takenLogs) . ']'
                : ' [NOT YET TAKEN]';

            return "• [ID: {$medication->id}] {$medication->name} ({$medication->dosage}) — scheduled at {$timesStr}{$takenStr}";
        })->implode("\n");
    }

    private function buildTaskContext(int $elderlyProfileId, Carbon $date): string
    {
        return Checklist::where('elderly_id', $elderlyProfileId)
            ->whereDate('due_date', $date)
            ->get()
            ->map(function (Checklist $task) {
                $status = $task->is_completed ? '✅ Done' : '⬜ Pending';
                $priority = $task->priority ? " [{$task->priority}]" : '';

                return "• [ID: {$task->id}] {$task->task} — {$status}{$priority}";
            })->implode("\n");
    }

    private function buildVitalsContext(int $elderlyProfileId, Carbon $windowStart): string
    {
        return HealthMetric::where('elderly_id', $elderlyProfileId)
            ->where('measured_at', '>=', $windowStart)
            ->orderBy('measured_at', 'desc')
            ->get()
            ->groupBy('type')
            ->map(function ($records, $type) {
                $latest = $records->first();
                $value = $latest->value_text ?? $latest->value;
                $unit = $latest->unit ? " {$latest->unit}" : '';

                return "• {$type}: {$value}{$unit} (at " . Carbon::parse($latest->measured_at)->format('g:i A') . ")";
            })->implode("\n");
    }

    private function buildRangeVitalsTrendContext(int $elderlyProfileId, Carbon $windowStart, Carbon $windowEnd): string
    {
        $summary = HealthMetric::where('elderly_id', $elderlyProfileId)
            ->whereBetween('measured_at', [$windowStart, $windowEnd])
            ->orderBy('measured_at', 'asc')
            ->get()
            ->groupBy('type')
            ->map(function ($records, $type) {
                $numericRecords = $records->filter(fn (HealthMetric $metric) => $metric->value !== null)->values();
                $latest = $records->last();
                $latestVal = $latest->value_text ?? $latest->value;
                $unit = $latest->unit ? " {$latest->unit}" : '';
                $count = $records->count();

                if ($numericRecords->count() < 2) {
                    return "• {$type}: {$count} readings | Latest: {$latestVal}{$unit}";
                }

                $first = (float) $numericRecords->first()->value;
                $last = (float) $numericRecords->last()->value;
                $avg = round($numericRecords->avg('value'), 1);
                $min = $numericRecords->min('value');
                $max = $numericRecords->max('value');
                $direction = $last > $first
                    ? 'upward'
                    : ($last < $first ? 'downward' : 'stable');

                return "• {$type}: {$count} readings | Avg: {$avg}{$unit} | Range: {$min}-{$max}{$unit} | Trend: {$direction} | Latest: {$latestVal}{$unit}";
            })
            ->implode("\n");

        return $summary !== '' ? $summary : '• No vitals recorded in the selected date range.';
    }

    private function resolveVitalsDateRange(string $userMessage, Carbon $referenceDate): array
    {
        $defaultStart = $referenceDate->copy()->subDays(7)->startOfDay();
        $defaultEnd = $referenceDate->copy()->endOfDay();

        $message = strtolower(trim($userMessage));
        if ($message === '') {
            return [
                'start' => $defaultStart,
                'end' => $defaultEnd,
                'label' => 'last 7 days',
            ];
        }

        if (preg_match('/\b(today)\b/i', $message) === 1) {
            return [
                'start' => $referenceDate->copy()->startOfDay(),
                'end' => $referenceDate->copy()->endOfDay(),
                'label' => 'today',
            ];
        }

        if (preg_match('/\b(yesterday)\b/i', $message) === 1) {
            $yesterday = $referenceDate->copy()->subDay();

            return [
                'start' => $yesterday->copy()->startOfDay(),
                'end' => $yesterday->copy()->endOfDay(),
                'label' => 'yesterday',
            ];
        }

        if (preg_match('/\b(last|past)\s+(\d{1,3})\s+(day|days|week|weeks|month|months)\b/i', $message, $matches) === 1) {
            $amount = max(1, min((int) $matches[2], 365));
            $unit = strtolower($matches[3]);

            $start = match ($unit) {
                'day', 'days' => $referenceDate->copy()->subDays($amount),
                'week', 'weeks' => $referenceDate->copy()->subWeeks($amount),
                default => $referenceDate->copy()->subMonths($amount),
            };

            return [
                'start' => $start->startOfDay(),
                'end' => $referenceDate->copy()->endOfDay(),
                'label' => "last {$amount} {$unit}",
            ];
        }

        if (preg_match('/\b(this\s+week|past\s+week|last\s+week|weekly|week|7\s+days)\b/i', $message) === 1) {
            return [
                'start' => $referenceDate->copy()->subDays(7)->startOfDay(),
                'end' => $referenceDate->copy()->endOfDay(),
                'label' => 'last 7 days',
            ];
        }

        if (preg_match('/\b(this\s+month|past\s+month|last\s+month|month|30\s+days)\b/i', $message) === 1) {
            return [
                'start' => $referenceDate->copy()->subMonth()->startOfDay(),
                'end' => $referenceDate->copy()->endOfDay(),
                'label' => 'last 1 month',
            ];
        }

        if (preg_match('/\b(last\s+3\s+months|past\s+3\s+months|3\s+months|90\s+days)\b/i', $message) === 1) {
            return [
                'start' => $referenceDate->copy()->subMonths(3)->startOfDay(),
                'end' => $referenceDate->copy()->endOfDay(),
                'label' => 'last 3 months',
            ];
        }

        if (preg_match('/\bfrom\s+(.+?)\s+to\s+(.+)$/i', $userMessage, $matches) === 1) {
            $start = $this->safeParseDate($matches[1]);
            $end = $this->safeParseDate($matches[2]);

            if ($start && $end) {
                if ($start->gt($end)) {
                    [$start, $end] = [$end, $start];
                }

                return [
                    'start' => $start->startOfDay(),
                    'end' => $end->endOfDay(),
                    'label' => $start->format('M j, Y') . ' to ' . $end->format('M j, Y'),
                ];
            }
        }

        if (preg_match('/\bbetween\s+(.+?)\s+and\s+(.+)$/i', $userMessage, $matches) === 1) {
            $start = $this->safeParseDate($matches[1]);
            $end = $this->safeParseDate($matches[2]);

            if ($start && $end) {
                if ($start->gt($end)) {
                    [$start, $end] = [$end, $start];
                }

                return [
                    'start' => $start->startOfDay(),
                    'end' => $end->endOfDay(),
                    'label' => $start->format('M j, Y') . ' to ' . $end->format('M j, Y'),
                ];
            }
        }

        if (preg_match('/\bsince\s+(.+)$/i', $userMessage, $matches) === 1) {
            $start = $this->safeParseDate($matches[1]);
            if ($start) {
                return [
                    'start' => $start->startOfDay(),
                    'end' => $referenceDate->copy()->endOfDay(),
                    'label' => 'since ' . $start->format('M j, Y'),
                ];
            }
        }

        return [
            'start' => $defaultStart,
            'end' => $defaultEnd,
            'label' => 'last 7 days',
        ];
    }

    private function safeParseDate(string $dateText): ?Carbon
    {
        $dateText = trim($dateText, " \t\n\r\0\x0B,.");

        if ($dateText === '') {
            return null;
        }

        try {
            return Carbon::parse($dateText);
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildMedicalInfoContext($profile): string
    {
        if (!$profile) {
            return '';
        }

        $conditions = $this->formatListContext($profile->medical_conditions, 'None noted');
        $allergies = $this->formatListContext($profile->allergies, 'None noted');

        return "Medical conditions: {$conditions}\nAllergies: {$allergies}";
    }

    private function buildCaregiverMedicationContext(int $elderlyProfileId, Carbon $weekAgo, Carbon $today): string
    {
        $allLogs = MedicationLog::where('elderly_id', $elderlyProfileId)
            ->whereBetween('scheduled_time', [$weekAgo, $today])
            ->with('medication')
            ->get();

        $totalScheduled = $allLogs->count();
        $totalTaken = $allLogs->where('is_taken', true)->count();
        $adherenceRate = $totalScheduled > 0 ? round(($totalTaken / $totalScheduled) * 100, 1) : 0;
        $missedMeds = $allLogs->where('is_taken', false)
            ->map(function ($log) {
                $medName = $log->medication->name ?? 'Unknown';
                $time = Carbon::parse($log->scheduled_time)->format('M j, g:i A');

                return "• {$medName} — missed at {$time}";
            })->implode("\n");

        return "Adherence Rate: {$adherenceRate}% ({$totalTaken}/{$totalScheduled} doses taken)\nMissed Doses:\n" . ($missedMeds ?: '• None');
    }

    private function buildCaregiverVitalsContext(int $elderlyProfileId, Carbon $weekAgo, Carbon $today): string
    {
        return HealthMetric::where('elderly_id', $elderlyProfileId)
            ->whereBetween('measured_at', [$weekAgo, $today])
            ->orderBy('measured_at', 'desc')
            ->get()
            ->groupBy('type')
            ->map(function ($records, $type) {
                $numericRecords = $records->filter(fn (HealthMetric $metric) => $metric->value !== null);
                $latest = $records->first();
                $latestVal = $latest->value_text ?? $latest->value;
                $unit = $latest->unit ?? '';
                $count = $records->count();
                $avg = $numericRecords->isNotEmpty() ? round($numericRecords->avg('value'), 1) : 'n/a';
                $min = $numericRecords->isNotEmpty() ? $numericRecords->min('value') : 'n/a';
                $max = $numericRecords->isNotEmpty() ? $numericRecords->max('value') : 'n/a';

                return "• {$type}: {$count} readings | Avg: {$avg} | Range: {$min}-{$max} | Latest: {$latestVal} {$unit}";
            })->implode("\n");
    }

    private function buildCaregiverTaskContext(int $elderlyProfileId, Carbon $weekAgo, Carbon $today): string
    {
        $tasks = Checklist::where('elderly_id', $elderlyProfileId)
            ->whereBetween('due_date', [$weekAgo, $today])
            ->get();

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('is_completed', true)->count();
        $taskRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

        return "Completion Rate: {$taskRate}% ({$completedTasks}/{$totalTasks} tasks)";
    }

    private function formatListContext(mixed $value, string $fallback): string
    {
        if (is_array($value)) {
            return empty($value) ? $fallback : implode(', ', $value);
        }

        return blank($value) ? $fallback : (string) $value;
    }

    /**
     * Return action events emitted during the latest AI response and clear the queue.
     */
    public function consumeActionEvents(): array
    {
        $events = $this->actionEvents;
        $this->actionEvents = [];

        return $events;
    }

    /**
     * Queue a frontend action event to be returned with chat responses.
     */
    private function pushActionEvent(array $event): void
    {
        $this->actionEvents[] = $event;
    }

    /**
     * Resolve which scheduled dose slot to mark as taken so UI check-off stays in sync.
     */
    private function resolveMedicationScheduledTime(Medication $medication, int $elderlyProfileId, mixed $requestedTime, Carbon $now): Carbon
    {
        $today = $now->copy()->startOfDay();

        if (is_string($requestedTime) && preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $requestedTime) === 1) {
            return Carbon::parse($today->format('Y-m-d') . ' ' . $requestedTime);
        }

        $times = collect($medication->scheduleTimesForDate($today))
            ->filter(fn ($time) => is_string($time) && preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $time) === 1)
            ->values();

        if ($times->isEmpty()) {
            return $now;
        }

        $scheduledSlots = $times
            ->map(fn (string $time) => Carbon::parse($today->format('Y-m-d') . ' ' . $time))
            ->values();

        $takenTimeKeys = MedicationLog::query()
            ->where('elderly_id', $elderlyProfileId)
            ->where('medication_id', $medication->id)
            ->whereDate('scheduled_time', $today)
            ->where('is_taken', true)
            ->get(['scheduled_time'])
            ->map(fn (MedicationLog $log) => $log->scheduled_time->format('H:i'))
            ->values();

        $untakenSlots = $scheduledSlots
            ->reject(fn (Carbon $slot) => $takenTimeKeys->contains($slot->format('H:i')))
            ->values();

        // Prefer doses that are due now or soon (within +60m), then nearest untaken slot.
        $dueOrNearSlots = $untakenSlots
            ->filter(fn (Carbon $slot) => $slot->lte($now->copy()->addMinutes(60)))
            ->values();

        $candidates = $dueOrNearSlots->isNotEmpty()
            ? $dueOrNearSlots
            : ($untakenSlots->isNotEmpty() ? $untakenSlots : $scheduledSlots);

        /** @var Carbon $nearest */
        $nearest = $candidates
            ->sortBy(fn (Carbon $slot) => $slot->diffInMinutes($now))
            ->first();

        return $nearest ?? $now;
    }
}
