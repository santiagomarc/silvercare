<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * ElderlyHeroAction — determines the single most urgent action for the user
 * and renders a prominent hero card.
 *
 * Priority: Overdue meds > Active meds > Unrecorded vitals > Incomplete tasks > All done
 */
class ElderlyHeroAction extends Component
{
    public string $actionType;   // 'medication' | 'vital' | 'checklist' | 'done'
    public string $headline;
    public string $subtext;
    public string $ctaLabel;
    public string $ctaAction;    // Alpine method or route
    public string $gradient;     // Tailwind gradient classes
    public string $icon;
    public int    $overallProgress;

    public function __construct(
        Collection $medications,
        Collection $medicationLogs,
        array      $vitalsData,
        Collection $checklists,
        int        $dailyGoalsProgress = 0,
    ) {
        $this->overallProgress = $dailyGoalsProgress;
        $this->resolve($medications, $medicationLogs, $vitalsData, $checklists);
    }

    private function resolve(Collection $meds, Collection $logs, array $vitals, Collection $checklists): void
    {
        // 1. Check for overdue or active medications
        $now = Carbon::now();
        foreach ($meds as $med) {
            foreach (($med->times_of_day ?? []) as $time) {
                $logKey = $med->id . '_' . $time;
                $log = $logs->get($logKey);
                if ($log && $log->is_taken) continue;

                $scheduled = Carbon::parse(today()->format('Y-m-d') . ' ' . $time);
                $windowStart = $scheduled->copy()->subMinutes(60);
                $windowEnd   = $scheduled->copy()->addMinutes(60);

                if ($now->between($windowStart, $windowEnd)) {
                    $this->actionType = 'medication';
                    $this->headline   = 'Time to take ' . $med->name;
                    $this->subtext    = $med->dosage . ' — scheduled for ' . $scheduled->format('g:i A');
                    $this->ctaLabel   = 'Mark as Taken';
                    $this->ctaAction  = "toggleEntry(\$refs['med_{$med->id}_{$time}'])";
                    $this->gradient   = 'from-green-500 to-emerald-600';
                    $this->icon       = '💊';
                    return;
                }

                if ($now->gt($windowEnd)) {
                    $this->actionType = 'medication';
                    $this->headline   = 'Missed: ' . $med->name;
                    $this->subtext    = $med->dosage . ' was due at ' . $scheduled->format('g:i A');
                    $this->ctaLabel   = 'Take Now (Late)';
                    $this->ctaAction  = "toggleEntry(\$refs['med_{$med->id}_{$time}'])";
                    $this->gradient   = 'from-red-500 to-rose-600';
                    $this->icon       = '⚠️';
                    return;
                }
            }
        }

        // 2. Check for unrecorded vitals
        foreach ($vitals as $type => $data) {
            if (!($data['recorded'] ?? false)) {
                $names = [
                    'blood_pressure' => 'Blood Pressure',
                    'sugar_level'    => 'Sugar Level',
                    'temperature'    => 'Temperature',
                    'heart_rate'     => 'Heart Rate',
                ];
                $this->actionType = 'vital';
                $this->headline   = 'Record your ' . ($names[$type] ?? $type);
                $this->subtext    = 'Daily vital check helps your caregiver monitor your health';
                $this->ctaLabel   = 'Record Now';
                $this->ctaAction  = "openModal('{$type}')";
                $this->gradient   = 'from-blue-500 to-indigo-600';
                $this->icon       = '❤️';
                return;
            }
        }

        // 3. Check for incomplete checklists
        $incomplete = $checklists->where('is_completed', false)->first();
        if ($incomplete) {
            $this->actionType = 'checklist';
            $this->headline   = $incomplete->title;
            $this->subtext    = $incomplete->description ?? 'Complete this task to grow your garden';
            $this->ctaLabel   = 'View Tasks';
            $this->ctaAction  = "switchTab('today')";
            $this->gradient   = 'from-amber-500 to-orange-500';
            $this->icon       = '📋';
            return;
        }

        // 4. All done!
        $this->actionType = 'done';
        $this->headline   = 'All caught up! Great job! 🎉';
        $this->subtext    = 'You\'ve completed all your tasks, medications, and vitals for today.';
        $this->ctaLabel   = 'Wellness Center';
        $this->ctaAction  = "window.location.href='" . route('elderly.wellness.index') . "'";
        $this->gradient   = 'from-emerald-400 to-teal-500';
        $this->icon       = '🌟';
    }

    public function render(): View|Closure|string
    {
        return view('components.elderly-hero-action');
    }
}
