<?php

namespace App\Console\Commands;

use App\Models\ChatMessage;
use App\Models\HealthMetric;
use App\Models\UserProfile;
use Carbon\Carbon;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TrackCognitiveSentiment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:track-cognitive-sentiment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze elderly user chat logs from the last 24 hours to generate a Cognitive Sentiment & Mood score.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $elderlies = UserProfile::where('user_type', 'elderly')->get();

        foreach ($elderlies as $elderly) {
            $user = $elderly->user;
            if (!$user) {
                continue;
            }

            // Get chat messages from the last 24 hours
            $twentyFourHoursAgo = Carbon::now()->subHours(24);
            $recentMessages = ChatMessage::whereHas('chatSession', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('created_at', '>=', $twentyFourHoursAgo)
            ->where('role', 'user') // Only analyze user messages
            ->orderBy('created_at', 'asc')
            ->get();

            if ($recentMessages->isEmpty()) {
                continue;
            }

            $chatLog = $recentMessages->map(function ($msg) {
                return $msg->created_at->format('H:i') . ' - User: ' . $msg->content;
            })->implode("\n");

            $this->info("Analyzing logs for user: {$user->name}");

            $prompt = <<<PROMPT
You are an expert geriatric sentiment analyst. Read the following chat logs from an elderly user over the last 24 hours.
Please output a strict JSON response with no markdown formatting.
Evaluate two metrics on a scale of 1 to 10 (1 is lowest, 10 is highest):
- "mood_score": How positive, happy, or content do they seem? (1=very sad/distressed, 10=very happy/optimistic)
- "confusion_index": How confused, forgetful, or anxious about daily tasks do they seem? (1=clear/sharp, 10=highly confused/disoriented)
- "analysis": A 1-sentence brief explanation.

CHAT LOG:
{$chatLog}

OUTPUT STRICT JSON ONLY: {"mood_score": int, "confusion_index": int, "analysis": "string"}
PROMPT;

            try {
                $response = Gemini::generativeModel('gemini-2.5-flash')->generateContent($prompt);
                $jsonStr = trim(str_replace(['```json', '```'], '', $response->text()));
                $result = json_decode($jsonStr, true);

                if (isset($result['mood_score']) && isset($result['confusion_index'])) {
                    // Save as HealthMetrics
                    HealthMetric::create([
                        'elderly_id' => $elderly->id,
                        'type' => 'ai_mood_score',
                        'value' => $result['mood_score'],
                        'measured_at' => now(),
                        'source' => 'system',
                        'notes' => $result['analysis'] ?? 'AI Generated',
                    ]);

                    HealthMetric::create([
                        'elderly_id' => $elderly->id,
                        'type' => 'ai_confusion_index',
                        'value' => $result['confusion_index'],
                        'measured_at' => now(),
                        'source' => 'system',
                        'notes' => $result['analysis'] ?? 'AI Generated',
                    ]);

                    $this->info("Successfully tracked sentiment for {$user->name}. Mood: {$result['mood_score']}, Confusion: {$result['confusion_index']}");
                }
            } catch (\Exception $e) {
                Log::error('Failed to analyze cognitive sentiment for user ' . $user->id . ': ' . $e->getMessage());
                $this->error('Failed: ' . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
