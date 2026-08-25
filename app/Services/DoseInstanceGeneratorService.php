<?php

namespace App\Services;

use App\Models\DoseInstance;
use App\Models\Medication;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DoseInstanceGeneratorService
{
    public const DEFAULT_HORIZON_DAYS = 7;

    /**
     * Generate pending dose instances for a single medication over a date range.
     */
    public function generateForMedication(Medication $medication, ?Carbon $startDate = null, int $daysAhead = self::DEFAULT_HORIZON_DAYS): Collection
    {
        if (!$medication->is_active) {
            return collect();
        }

        $elderly = $medication->elderly;
        $timezone = $elderly?->timezone ?? config('app.timezone', 'Asia/Manila');

        $start = ($startDate ? $startDate->copy() : Carbon::now($timezone))->startOfDay();
        $end = $start->copy()->addDays($daysAhead)->endOfDay();

        $generated = collect();

        $currentDay = $start->copy();
        while ($currentDay->lte($end)) {
            // Check start_date and end_date bounds
            if ($medication->start_date && $currentDay->lt($medication->start_date->copy()->startOfDay())) {
                $currentDay->addDay();
                continue;
            }

            if ($medication->end_date && $currentDay->gt($medication->end_date->copy()->endOfDay())) {
                $currentDay->addDay();
                continue;
            }

            if (!$medication->isScheduledForDate($currentDay)) {
                $currentDay->addDay();
                continue;
            }

            $scheduleTimes = $medication->scheduleTimesForDate($currentDay);

            foreach ($scheduleTimes as $timeStr) {
                try {
                    $localDt = Carbon::parse($currentDay->format('Y-m-d') . ' ' . $timeStr, $timezone);
                    $scheduledAtUtc = $localDt->copy()->setTimezone('UTC');

                    $instance = DoseInstance::firstOrCreate(
                        [
                            'elderly_id' => $medication->elderly_id,
                            'medication_id' => $medication->id,
                            'scheduled_at_utc' => $scheduledAtUtc,
                        ],
                        [
                            'local_date' => $currentDay->format('Y-m-d'),
                            'timezone' => $timezone,
                            'state' => 'pending',
                            'version' => 1,
                        ]
                    );

                    $generated->push($instance);
                } catch (\Exception $e) {
                    Log::warning("Failed to create dose instance for med {$medication->id} at {$timeStr}: " . $e->getMessage());
                }
            }

            $currentDay->addDay();
        }

        return $generated;
    }

    /**
     * Generate pending dose instances for all active medications in the system.
     */
    public function generateForActiveMedications(?Carbon $startDate = null, int $daysAhead = self::DEFAULT_HORIZON_DAYS): int
    {
        $medications = Medication::where('is_active', true)
            ->with(['elderly', 'schedules'])
            ->get();

        $total = 0;
        foreach ($medications as $medication) {
            $created = $this->generateForMedication($medication, $startDate, $daysAhead);
            $total += $created->count();
        }

        return $total;
    }
}
