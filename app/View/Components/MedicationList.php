<?php

namespace App\View\Components;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Collection;

class MedicationList extends Component
{
    public Collection $medications;
    public Collection $logs;
    public Collection $sortedDoses;
    public int $totalDoses = 0;
    public int $takenDoses = 0;
    public int $progress = 0;

    /**
     * Create a new component instance.
     */
    public function __construct(Collection $medications, Collection $logs)
    {
        $this->medications = $medications;
        $this->logs = $logs;
        
        $this->calculateProgressAndSortDoses();
    }

    private function calculateProgressAndSortDoses(): void
    {
        $today = Carbon::today();
        $doses = collect();

        foreach ($this->medications as $med) {
            $times = $med->scheduleTimesForDate($today);
            $this->totalDoses += count($times);
            foreach ($times as $t) {
                // Ensure correct format mapping for log key
                $timeFormatted = Carbon::parse($t)->format('H:i');
                $lk = $med->id . '_' . $timeFormatted;
                $log = $this->logs->get($lk);
                
                if ($log?->is_taken) {
                    $this->takenDoses++;
                }

                $doses->push([
                    'medication' => $med,
                    'time' => $timeFormatted,
                    'time_carbon' => Carbon::parse($t),
                    'log_key' => $lk,
                    'log' => $log,
                ]);
            }
        }
        
        // Sort doses based on time (earliest to latest)
        $this->sortedDoses = $doses->sortBy(function($dose) {
            return $dose['time_carbon']->format('H:i:s');
        })->values();

        $this->progress = $this->totalDoses > 0 ? (int) round(($this->takenDoses / $this->totalDoses) * 100) : 0;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.medication-list');
    }
}
