<?php

namespace App\Console\Commands;

use App\Models\HealthMetric;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\UserProfile;
use App\Services\AiAssistantService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDailyReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'silvercare:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily reminders for vitals, mood, and check for missed medications';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService, AiAssistantService $aiAssistantService)
    {
        $this->info('Starting daily reminders check...');
        
        $today = Carbon::today();
        $now = Carbon::now();
        
        // Get all elderly profiles (user_type = 'elderly')
        $elderlyProfiles = UserProfile::where('user_type', 'elderly')->get();
        
        $this->info("Found {$elderlyProfiles->count()} elderly profiles to check");
        
        foreach ($elderlyProfiles as $profile) {
            $elderlyId = $profile->id;
            
            // -----------------------------------------------------
            // 1. Check for MISSED MEDICATIONS (past grace period)
            // -----------------------------------------------------
            $this->checkMissedMedications($notificationService, $profile, $now);
            
            // -----------------------------------------------------
            // 2. Check if VITALS have been logged today
            // -----------------------------------------------------
            $vitalsToday = HealthMetric::where('elderly_id', $elderlyId)
                ->whereIn('type', ['blood_pressure', 'sugar_level', 'temperature', 'heart_rate'])
                ->whereDate('measured_at', $today)
                ->exists();
            
            if (!$vitalsToday) {
                // Only send reminder if it's after 10 AM
                if ($now->hour >= 10) {
                    try {
                        $notificationService->createDailyReminderNotification($elderlyId, 'vitals');
                        $this->info("  - Sent vitals reminder to profile #{$elderlyId}");
                    } catch (\Exception $e) {
                        // Duplicate prevention - custom_id already exists
                        $this->line("  - Vitals reminder already sent to profile #{$elderlyId}");
                    }
                }
            }
            
            // -----------------------------------------------------
            // 3. Check if MOOD has been logged today
            // -----------------------------------------------------
            $moodToday = HealthMetric::where('elderly_id', $elderlyId)
                ->where('type', 'mood')
                ->whereDate('measured_at', $today)
                ->exists();
            
            if (!$moodToday) {
                // Only send reminder if it's after 9 AM
                if ($now->hour >= 9) {
                    try {
                        $notificationService->createDailyReminderNotification($elderlyId, 'mood');
                        $this->info("  - Sent mood reminder to profile #{$elderlyId}");
                    } catch (\Exception $e) {
                        // Duplicate prevention
                        $this->line("  - Mood reminder already sent to profile #{$elderlyId}");
                    }
                }
            }

            // ---------------------------------------------------------
            // 4. AI MORNING HEALTH SUMMARY (once per day, after 7 AM)
            // ---------------------------------------------------------
            if ($now->hour >= 7 && $now->hour < 12) {
                $summaryCustomId = "ai_summary_{$elderlyId}_{$today->format('Y-m-d')}";
                $alreadySent = \App\Models\Notification::where('custom_id', $summaryCustomId)->exists();

                if (!$alreadySent && $profile->user) {
                    try {
                        $summary = $aiAssistantService->generateDailySummary($profile->user);

                        if (!empty($summary)) {
                            $notificationService->createNotification([
                                'elderly_id' => $elderlyId,
                                'type'       => 'ai_daily_summary',
                                'title'      => '🌟 Good Morning Health Summary',
                                'message'    => $summary,
                                'severity'   => 'reminder',
                                'custom_id'  => $summaryCustomId,
                            ]);
                            $this->info("  - Sent AI morning summary to profile #{$elderlyId}");
                        }
                    } catch (\Exception $e) {
                        $this->error("  - AI summary failed for profile #{$elderlyId}: {$e->getMessage()}");
                    }
                }
            }
        }
        
        $this->info('Daily reminders check completed!');
        
        return Command::SUCCESS;
    }
    
    /**
     * Check for missed medications and send notifications
     */
    private function checkMissedMedications(NotificationService $notificationService, UserProfile $profile, Carbon $now)
    {
        $elderlyId = $profile->id;
        $today = Carbon::today();
        
        // Get all active medications for this elderly
        $medications = Medication::where('elderly_id', $elderlyId)
            ->where('is_active', true)
            ->get();
        
        foreach ($medications as $medication) {
            $scheduledTimes = is_array($medication->scheduled_times) 
                ? $medication->scheduled_times 
                : json_decode($medication->scheduled_times, true) ?? [];
            
            foreach ($scheduledTimes as $scheduledTime) {
                // Parse the scheduled time for today
                $scheduledDateTime = Carbon::parse($today->format('Y-m-d') . ' ' . $scheduledTime);
                
                // Check if 1 hour (grace period) has passed
                $gracePeriodEnd = $scheduledDateTime->copy()->addHour();
                
                if ($now->greaterThan($gracePeriodEnd)) {
                    // Check if medication was logged for this time
                    $wasTaken = MedicationLog::where('elderly_id', $elderlyId)
                        ->where('medication_id', $medication->id)
                        ->whereDate('scheduled_time', $today)
                        ->where('scheduled_time', $scheduledDateTime)
                        ->where('is_taken', true)
                        ->exists();
                    
                    if (!$wasTaken) {
                        // Create a unique custom_id to prevent duplicate notifications
                        $customId = "missed_med_{$medication->id}_{$scheduledDateTime->format('Y-m-d_H-i')}";
                        
                        // Check if this notification was already sent
                        $alreadySent = \App\Models\Notification::where('custom_id', $customId)->exists();
                        
                        if (!$alreadySent) {
                            $notificationService->createMedicationMissedNotification(
                                $elderlyId,
                                $medication->name,
                                $scheduledDateTime->format('g:i A')
                            );
                            
                            // Set the custom_id on the notification we just created
                            $lastNotification = \App\Models\Notification::where('elderly_id', $elderlyId)
                                ->where('type', 'medication_missed')
                                ->latest()
                                ->first();
                            if ($lastNotification) {
                                $lastNotification->update(['custom_id' => $customId]);
                            }
                            
                            $this->info("  - Sent missed medication notification for {$medication->name} at {$scheduledTime} to profile #{$elderlyId}");
                        }
                    }
                }
            }
        }
    }
}
