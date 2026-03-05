<?php

namespace App\Services;

use App\Models\MedicationLog;
use Carbon\Carbon;

class MedicationAdherenceService
{
    /**
     * Calculate a 7-day medication adherence summary for an elderly profile.
     *
     * @param  \App\Models\UserProfile  $elderly
     * @return array{totalMedications: int, totalScheduled: int, totalTaken: int, adherenceRate: int|null, lowStockCount: int, medications: array}
     */
    public function weekSummary($elderly): array
    {
        $medications = $elderly->trackedMedications()->where('is_active', true)->get();
        $last7Days = Carbon::today()->subDays(6);

        // Pre-fetch ALL taken logs for the 7-day window in one query
        $medIds = $medications->pluck('id');
        $allLogs = MedicationLog::whereIn('medication_id', $medIds)
            ->whereDate('scheduled_time', '>=', $last7Days)
            ->whereDate('scheduled_time', '<=', Carbon::today())
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

            for ($date = $last7Days->copy(); $date <= Carbon::today(); $date->addDay()) {
                $dayOfWeek = $date->format('l');
                if (in_array($dayOfWeek, $med->days_of_week ?? [])) {
                    $doseCount = count($med->times_of_day ?? []);
                    $scheduled += $doseCount;

                    $key = $med->id . '_' . $date->format('Y-m-d');
                    $taken += ($allLogs->get($key)?->count() ?? 0);
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
}
