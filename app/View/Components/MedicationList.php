<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Collection;

class MedicationList extends Component
{
    public Collection $medications;
    public Collection $logs;
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
        
        $this->calculateProgress();
    }

    private function calculateProgress(): void
    {
        foreach ($this->medications as $med) {
            $times = $med->times_of_day ?? [];
            $this->totalDoses += count($times);
            foreach ($times as $t) {
                $lk = $med->id . '_' . $t;
                if ($this->logs->get($lk)?->is_taken) {
                    $this->takenDoses++;
                }
            }
        }
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
