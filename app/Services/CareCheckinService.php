<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\CareCheckin;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CareCheckinService
{
    public function __construct(
        protected AlertDeliveryService $alertDeliveryService,
        protected NotificationService $notificationService,
    ) {
    }

    /**
     * Record or update a patient's daily check-in.
     */
    public function recordCheckin(
        UserProfile $elderly,
        string $status = 'ok',
        ?string $notes = null,
        ?string $mood = null,
        string $source = 'web_button'
    ): CareCheckin {
        $tz = $elderly->timezone ?? config('app.timezone', 'Asia/Manila');
        $todayDate = Carbon::today($tz)->toDateString();

        $checkin = CareCheckin::updateOrCreate(
            [
                'elderly_id' => $elderly->id,
                'checkin_date' => $todayDate,
            ],
            [
                'status' => $status,
                'notes' => $notes,
                'mood' => $mood,
                'checked_in_at' => Carbon::now(),
                'source' => $source,
            ]
        );

        $patientName = $elderly->user?->name ?? 'Your patient';

        // If senior requested help, trigger an immediate alert for the caregiver
        if ($status === 'need_help') {
            $alert = Alert::create([
                'elderly_id' => $elderly->id,
                'severity' => 'warning',
                'source_type' => 'check_in_need_help',
                'source_id' => $checkin->id,
                'title' => "⚠️ {$patientName} Requested Assistance",
                'message' => "{$patientName} checked in and indicated they need assistance." . ($notes ? " Note: \"{$notes}\"" : ""),
                'metadata' => [
                    'checkin_id' => $checkin->id,
                    'notes' => $notes,
                    'mood' => $mood,
                    'source' => $source,
                    'timestamp' => Carbon::now()->toISOString(),
                ],
                'state' => 'open',
                'escalate_at' => Carbon::now()->addMinutes(30),
            ]);

            $this->alertDeliveryService->deliver($alert);
        }

        return $checkin;
    }

    /**
     * Check for missed daily check-ins across all active patients.
     */
    public function checkMissedCheckins(): int
    {
        $activePatients = UserProfile::where('user_type', 'elderly')
            ->whereNotNull('caregiver_id')
            ->whereNull('archived_at')
            ->get();

        $missedCount = 0;

        foreach ($activePatients as $patient) {
            $tz = $patient->timezone ?? config('app.timezone', 'Asia/Manila');
            $nowLocal = Carbon::now($tz);
            $todayDate = $nowLocal->toDateString();

            // Only check if it's already evening (after 18:00 local time)
            if ($nowLocal->hour < 18) {
                continue;
            }

            $existing = CareCheckin::where('elderly_id', $patient->id)
                ->where('checkin_date', $todayDate)
                ->first();

            if (!$existing) {
                $checkin = CareCheckin::create([
                    'elderly_id' => $patient->id,
                    'checkin_date' => $todayDate,
                    'status' => 'missed',
                    'notes' => 'No check-in received before cutoff.',
                    'source' => 'system_scheduler',
                ]);

                $patientName = $patient->user?->name ?? 'Patient';

                $alert = Alert::create([
                    'elderly_id' => $patient->id,
                    'severity' => 'warning',
                    'source_type' => 'check_in_missed',
                    'source_id' => $checkin->id,
                    'title' => "⚠️ Missing Daily Check-in: {$patientName}",
                    'message' => "{$patientName} has not completed their daily check-in today as of {$nowLocal->format('g:i A')}.",
                    'metadata' => [
                        'checkin_id' => $checkin->id,
                        'date' => $todayDate,
                    ],
                    'state' => 'open',
                    'escalate_at' => Carbon::now()->addMinutes(60),
                ]);

                $this->alertDeliveryService->deliver($alert);
                $missedCount++;
            }
        }

        return $missedCount;
    }
}
