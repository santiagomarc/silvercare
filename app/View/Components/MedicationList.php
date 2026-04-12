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
    public array $groupedDoses = [];
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
        
        $this->sortedDoses = $doses->sortBy(function($dose) {
            return $dose['time_carbon']->format('H:i:s');
        })->values();

        // Group doses by time of day
        // Morning (05:00-11:59), Afternoon (12:00-16:59), Evening (17:00-20:59), Night (21:00-04:59)
        $this->groupedDoses = [
            'Morning' => collect(),
            'Afternoon' => collect(),
            'Evening' => collect(),
            'Night' => collect(),
        ];

        foreach ($this->sortedDoses as $dose) {
            $hour = (int) $dose['time_carbon']->format('G');
            if ($hour >= 5 && $hour < 12) {
                $this->groupedDoses['Morning']->push($dose);
            } elseif ($hour >= 12 && $hour < 17) {
                $this->groupedDoses['Afternoon']->push($dose);
            } elseif ($hour >= 17 && $hour < 21) {
                $this->groupedDoses['Evening']->push($dose);
            } else {
                $this->groupedDoses['Night']->push($dose);
            }
        }

        // Remove empty groups for cleaner rendering
        $this->groupedDoses = array_filter($this->groupedDoses, function($group) {
            return $group->count() > 0;
        });

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
