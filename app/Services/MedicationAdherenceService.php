<?php

namespace App\Services;

use App\Models\MedicationLog;
use Carbon\Carbon;

class MedicationAdherenceService
{
    /**
     * Backward-compatible weekly summary.
     *
     * @param  \App\Models\UserProfile  $elderly
     * @return array{totalMedications: int, totalScheduled: int, totalTaken: int, adherenceRate: int|null, lowStockCount: int, medications: array}
     */
    public function weekSummary($elderly): array
    {
        return $this->summary($elderly, 7);
    }

    /**
     * Calculate medication adherence summary for configurable lookback windows.
     *
     * @param  \App\Models\UserProfile  $elderly
     * @return array{totalMedications: int, totalScheduled: int, totalTaken: int, adherenceRate: int|null, lowStockCount: int, medications: array}
     */
    public function summary($elderly, int $lookbackDays = 30): array
    {
        $days = max(1, $lookbackDays);
        $medications = $elderly->trackedMedications()->where('is_active', true)->with('schedules')->get();
        $startDate = Carbon::today()->subDays($days - 1);
        $today = Carbon::today();

        $medIds = $medications->pluck('id');
        $allLogs = MedicationLog::whereIn('medication_id', $medIds)
            ->whereDate('scheduled_time', '>=', $startDate)
            ->whereDate('scheduled_time', '<=', $today)
            ->where('is_taken', true)
            ->get()
            ->groupBy(fn ($log) => $log->medication_id . '_' . $log->scheduled_time->format('Y-m-d'));

        $totalScheduled = 0;
        $totalTaken = 0;
        $lowStockCount = 0;
        $medDetails = [];

        foreach ($medications as $med) {
            $scheduled = 0;
            $taken = 0;

            for ($date = $startDate->copy(); $date->lte($today); $date->addDay()) {
                if ($this->isScheduledForDate($med, $date)) {
                    $doseCount = count($med->scheduleTimesForDate($date));
                    $scheduled += $doseCount;

                    $key = $med->id . '_' . $date->format('Y-m-d');
                    $takenForDay = $allLogs->get($key)?->count() ?? 0;
                    $taken += min($takenForDay, $doseCount);
                }
            }

            $totalScheduled += $scheduled;
            $totalTaken += $taken;

            if ($med->track_inventory && $med->current_stock <= ($med->low_stock_threshold ?? 5)) {
                $lowStockCount++;
            }

            $medDetails[] = [
                'name' => $med->name,
                'scheduled' => $scheduled,
                'taken' => $taken,
                'adherence' => $scheduled > 0 ? round(($taken / $scheduled) * 100) : null,
                'lowStock' => $med->track_inventory && $med->current_stock <= ($med->low_stock_threshold ?? 5),
                'stock' => $med->current_stock,
            ];
        }

        return [
            'totalMedications' => $medications->count(),
            'totalScheduled' => $totalScheduled,
            'totalTaken' => $totalTaken,
            'adherenceRate' => $totalScheduled > 0 ? round(($totalTaken / $totalScheduled) * 100) : null,
            'lowStockCount' => $lowStockCount,
            'medications' => $medDetails,
        ];
    }

    private function isScheduledForDate($medication, Carbon $date): bool
    {
        return $medication->isScheduledForDate($date);
    }
}
