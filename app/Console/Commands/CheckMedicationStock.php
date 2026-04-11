<?php

namespace App\Console\Commands;

use App\Models\Medication;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckMedicationStock extends Command
{
    protected $signature = 'medications:check-stock';
    protected $description = 'Check medication stock levels and create low-stock alerts';

    public function __construct(
        protected NotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking medication stock levels...');

        $medications = Medication::where('is_active', true)
            ->where('track_inventory', true)
            ->where('current_stock', '>', 0)
            ->with(['schedules', 'elderly.user'])
            ->get();

        $alertCount = 0;

        foreach ($medications as $medication) {
            $profile = $medication->elderly;
            if (!$profile) continue;

            // Calculate daily doses from schedules
            $dailyDoses = $medication->schedules()
                ->where('schedule_type', 'daily')
                ->count();

            // For weekly schedules, estimate daily average
            $weeklySchedules = $medication->schedules()
                ->where('schedule_type', 'weekly')
                ->get();

            foreach ($weeklySchedules as $schedule) {
                $daysCount = count($schedule->days_of_week ?? []);
                $dailyDoses += $daysCount / 7;
            }

            // Default to 1 if we can't calculate
            $dailyDoses = max(1, round($dailyDoses));

            // Alert if stock is <= 3 days of doses
            $threshold = $dailyDoses * 3;

            if ($medication->current_stock <= $threshold) {
                $daysLeft = $dailyDoses > 0
                    ? floor($medication->current_stock / $dailyDoses)
                    : $medication->current_stock;

                $caregiverProfile = $profile->caregiver;
                $caregiverName = $caregiverProfile?->user?->name ?? 'Caregiver';

                // Create notification for elderly user
                try {
                    $this->notificationService->createNotification([
                        'elderly_id' => $profile->id,
                        'type' => 'medication_refill',
                        'title' => '💊 Low Stock: ' . $medication->name,
                        'message' => "You have approximately {$daysLeft} day(s) of {$medication->name} remaining ({$medication->current_stock} doses left). Consider refilling soon.",
                        'severity' => $daysLeft <= 1 ? 'negative' : 'warning',
                        'metadata' => [
                            'medication_id' => $medication->id,
                            'medication_name' => $medication->name,
                            'current_stock' => $medication->current_stock,
                            'days_remaining' => $daysLeft,
                            'audience' => 'elderly',
                        ],
                    ]);

                    // Create caregiver-facing notification for dashboard activity stream
                    if ($caregiverProfile) {
                        $this->notificationService->createNotification([
                            'elderly_id' => $profile->id,
                            'type' => 'medication_refill_caregiver',
                            'title' => '🧑‍⚕️ Patient Low Stock: ' . $medication->name,
                            'message' => "{$profile->user?->name} is running low on {$medication->name} ({$medication->current_stock} doses left, ~{$daysLeft} day(s)).",
                            'severity' => $daysLeft <= 1 ? 'negative' : 'warning',
                            'metadata' => [
                                'medication_id' => $medication->id,
                                'medication_name' => $medication->name,
                                'current_stock' => $medication->current_stock,
                                'days_remaining' => $daysLeft,
                                'caregiver_profile_id' => $caregiverProfile->id,
                                'caregiver_name' => $caregiverName,
                                'audience' => 'caregiver',
                            ],
                        ]);
                    }

                    $alertCount++;
                    $this->line("  ⚠️  {$medication->name} ({$profile->user?->name}): {$medication->current_stock} doses left (~{$daysLeft} days)");

                } catch (\Exception $e) {
                    Log::warning("Failed to create refill alert for medication {$medication->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Done. Created {$alertCount} low-stock alert(s).");

        return self::SUCCESS;
    }
}
