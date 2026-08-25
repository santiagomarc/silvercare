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
    public function handle(
        NotificationService $notificationService,
        AiAssistantService $aiAssistantService,
        \App\Services\DoseAdministrationService $doseService
    )
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
            $this->checkMissedMedications($notificationService, $doseService, $profile, $now);
            
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
                // Only send reminder if it's after 2 PM
                if ($now->hour >= 14) {
                    try {
                        $notificationService->createDailyReminderNotification($elderlyId, 'mood');
                        $this->info("  - Sent mood reminder to profile #{$elderlyId}");
                    } catch (\Exception $e) {
                        // Duplicate prevention - custom_id already exists
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
                        $this->error("  - AI summary failed for profile #{$elderlyId}: " . $e->getMessage());
                    }
                }
            }
        }
        
        $this->info('Daily reminders check completed successfully!');
        
        return Command::SUCCESS;
    }
    
    /**
     * Check for missed medications and send notifications
     */
    private function checkMissedMedications(
        NotificationService $notificationService,
        \App\Services\DoseAdministrationService $doseService,
        UserProfile $profile,
        Carbon $now
    )
    {
        $elderlyId = $profile->id;
        $timezone = $profile->timezone ?: config('app.timezone', 'Asia/Manila');
        $today = Carbon::now($timezone)->startOfDay();
        $graceMinutes = \App\Services\DoseAdministrationService::DEFAULT_GRACE_MINUTES;

        // 1. Check existing pending DoseInstances that are overdue
        $overdueInstances = \App\Models\DoseInstance::where('elderly_id', $elderlyId)
            ->where('state', 'pending')
            ->where('scheduled_at_utc', '<=', $now->copy()->subMinutes($graceMinutes)->setTimezone('UTC'))
            ->get();

        foreach ($overdueInstances as $instance) {
            $doseService->markMissed($instance);
            $this->info("  - Marked missed dose instance #{$instance->id} for profile #{$elderlyId}");
        }

        // 2. Also check normalized schedules for any active medications for today
        $medications = Medication::where('elderly_id', $elderlyId)
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->with('schedules')
            ->get();

        foreach ($medications as $medication) {
            if (!$medication->isScheduledForDate($today)) {
                continue;
            }

            $scheduleTimes = $medication->scheduleTimesForDate($today);
            foreach ($scheduleTimes as $scheduledTime) {
                $scheduledDateTime = Carbon::parse($today->format('Y-m-d') . ' ' . $scheduledTime, $timezone);
                $gracePeriodEnd = $scheduledDateTime->copy()->addMinutes($graceMinutes);

                if ($now->greaterThan($gracePeriodEnd)) {
                    $scheduledUtc = $scheduledDateTime->copy()->setTimezone('UTC');
                    $instance = \App\Models\DoseInstance::where('elderly_id', $elderlyId)
                        ->where('medication_id', $medication->id)
                        ->where('scheduled_at_utc', $scheduledUtc)
                        ->first();

                    if (!$instance) {
                        $instance = \App\Models\DoseInstance::create([
                            'elderly_id' => $elderlyId,
                            'medication_id' => $medication->id,
                            'scheduled_at_utc' => $scheduledUtc,
                            'local_date' => $today->format('Y-m-d'),
                            'timezone' => $timezone,
                            'state' => 'pending',
                            'version' => 1,
                        ]);
                    }

                    if ($instance->isPending()) {
                        $doseService->markMissed($instance);
                        $this->info("  - Sent missed medication notification for {$medication->name} at {$scheduledTime} to profile #{$elderlyId}");
                    }
                }
            }
        }
    }
}
