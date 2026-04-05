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
 * Priority ladder:
 * 1) Overdue medication
 * 2) Current-window medication
 * 3) Unrecorded vitals
 * 4) Incomplete tasks/checklists
 * 5) Mood not logged today
 * 6) All done
 */
class ElderlyHeroAction extends Component
{
    public string $actionType;   // 'medication' | 'vital' | 'checklist' | 'mood' | 'done'
    public string $headline;
    public string $subtext;
    public string $nextUp;
    public string $ctaLabel;
    public string $ctaAction;    // Alpine method or route
    public string $gradient;     // Tailwind gradient classes
    public string $icon;
    public int    $overallProgress;

    // Medication-specific context for inline "I Took It" button
    public ?int    $medicationId = null;
    public ?string $scheduledTime = null;

    public function __construct(
        Collection $medications,
        Collection $medicationLogs,
        array      $vitalsData,
        Collection $checklists,
        bool       $moodRecorded = false,
        int        $dailyGoalsProgress = 0,
    ) {
        $this->overallProgress = $dailyGoalsProgress;
        $this->nextUp = 'You are all set for now.';
        $this->determineAction($medications, $medicationLogs, $vitalsData, $checklists, $moodRecorded);
    }

    private function determineAction(Collection $meds, Collection $logs, array $vitals, Collection $checklists, bool $moodRecorded): void
    {
        // Identify medication states for today's pending doses.
        $now = Carbon::now();
        $overdueDose = null;
        $activeDose = null;
        $nextUpcomingDose = null;

        foreach ($meds as $med) {
            $times = $med->scheduleTimesForDate(today());
            foreach ($times as $time) {
                $logKey = $med->id . '_' . $time;
                $log = $logs->get($logKey);
                if ($log && $log->is_taken) continue;

                $scheduled = Carbon::parse(today()->format('Y-m-d') . ' ' . $time);
                $windowStart = $scheduled->copy()->subMinutes(60);
                $windowEnd   = $scheduled->copy()->addMinutes(60);

                if ($now->gt($windowEnd)) {
                    if (!$overdueDose || $scheduled->lt($overdueDose['scheduled'])) {
                        $overdueDose = ['med' => $med, 'time' => $time, 'scheduled' => $scheduled];
                    }
                    continue;
                }

                if ($now->between($windowStart, $windowEnd)) {
                    if (!$activeDose || $scheduled->lt($activeDose['scheduled'])) {
                        $activeDose = ['med' => $med, 'time' => $time, 'scheduled' => $scheduled];
                    }
                    continue;
                }

                if ($scheduled->isFuture() && (!$nextUpcomingDose || $scheduled->lt($nextUpcomingDose['scheduled']))) {
                    $nextUpcomingDose = ['med' => $med, 'time' => $time, 'scheduled' => $scheduled];
                }
            }
        }

        // Next candidate: unrecorded vitals.
        $nextVital = null;
        foreach (['blood_pressure', 'sugar_level', 'temperature', 'heart_rate'] as $type) {
            $data = $vitals[$type] ?? ['recorded' => false];
            if (!($data['recorded'] ?? false)) {
                $names = [
                    'blood_pressure' => 'Blood Pressure',
                    'sugar_level'    => 'Sugar Level',
                    'temperature'    => 'Temperature',
                    'heart_rate'     => 'Heart Rate',
                ];
                $nextVital = [
                    'type' => $type,
                    'label' => $names[$type] ?? $type,
                ];
                break;
            }
        }

        // Next candidate: incomplete checklists.
        $incomplete = $checklists
            ->where('is_completed', false)
            ->sortBy(fn ($item) => ($item->due_time ?? '23:59') . '|' . ($item->task ?? ''))
            ->first();

        // Next candidate: mood not logged.
        $needsMoodLog = !$moodRecorded;

        $nextFrom = function (array $options): string {
            foreach ($options as $candidate) {
                if (filled($candidate)) {
                    return (string) $candidate;
                }
            }

            return 'You are all set for now.';
        };

        // 1) Overdue medication (red urgent)
        if ($overdueDose) {
            $this->actionType    = 'medication';
            $this->headline      = 'Overdue: ' . $overdueDose['med']->name;
            $this->subtext       = ($overdueDose['med']->dosage ?? 'Dose') . ' was due at ' . $overdueDose['scheduled']->format('g:i A');
            $this->nextUp        = $nextFrom([
                $activeDose ? 'Take ' . $activeDose['med']->name . ' (' . $activeDose['scheduled']->format('g:i A') . ')' : null,
                $nextVital ? 'Record ' . $nextVital['label'] : null,
                $incomplete ? 'Complete task: ' . $incomplete->task : null,
                $needsMoodLog ? "Log today's mood" : null,
                $nextUpcomingDose ? 'Next dose: ' . $nextUpcomingDose['med']->name . ' at ' . $nextUpcomingDose['scheduled']->format('g:i A') : null,
            ]);
            $this->ctaLabel      = 'View Medications';
            $this->ctaAction     = "switchTab('today')";
            $this->gradient      = 'from-red-600 to-rose-600';
            $this->icon          = '🚨';
            $this->medicationId  = $overdueDose['med']->id;
            $this->scheduledTime = $overdueDose['time'];
            return;
        }

        // 2) Current-window medication (blue active)
        if ($activeDose) {
            $this->actionType    = 'medication';
            $this->headline      = 'Time to take ' . $activeDose['med']->name;
            $this->subtext       = ($activeDose['med']->dosage ?? 'Dose') . ' — scheduled for ' . $activeDose['scheduled']->format('g:i A');
            $this->nextUp        = $nextFrom([
                $nextVital ? 'Record ' . $nextVital['label'] : null,
                $incomplete ? 'Complete task: ' . $incomplete->task : null,
                $needsMoodLog ? "Log today's mood" : null,
                $nextUpcomingDose ? 'Next dose: ' . $nextUpcomingDose['med']->name . ' at ' . $nextUpcomingDose['scheduled']->format('g:i A') : null,
            ]);
            $this->ctaLabel      = 'View Medications';
            $this->ctaAction     = "switchTab('today')";
            $this->gradient      = 'from-sky-500 to-blue-600';
            $this->icon          = '💊';
            $this->medicationId  = $activeDose['med']->id;
            $this->scheduledTime = $activeDose['time'];
            return;
        }

        // 3) Unrecorded vitals (teal)
        if ($nextVital) {
            $type = $nextVital['type'];
            $this->actionType = 'vital';
            $this->headline   = 'Record your ' . $nextVital['label'];
            $this->subtext    = 'A quick vital check keeps your care team updated.';
            $this->nextUp     = $nextFrom([
                $incomplete ? 'Complete task: ' . $incomplete->task : null,
                $needsMoodLog ? "Log today's mood" : null,
                $nextUpcomingDose ? 'Next dose: ' . $nextUpcomingDose['med']->name . ' at ' . $nextUpcomingDose['scheduled']->format('g:i A') : null,
            ]);
            $this->ctaLabel   = 'Record Now';
            $this->ctaAction  = "\$dispatch('open-vital-modal', { type: '{$type}' })";
            $this->gradient   = 'from-teal-500 to-cyan-600';
            $this->icon       = '🩺';
            return;
        }

        // 4) Incomplete tasks/checklists (amber)
        if ($incomplete) {
            $this->actionType = 'checklist';
            $this->headline   = $incomplete->task;
            $this->subtext    = $incomplete->description ?? 'Complete this task to grow your garden';
            $this->nextUp     = $nextFrom([
                $needsMoodLog ? "Log today's mood" : null,
                $nextUpcomingDose ? 'Next dose: ' . $nextUpcomingDose['med']->name . ' at ' . $nextUpcomingDose['scheduled']->format('g:i A') : null,
            ]);
            $this->ctaLabel   = 'View Tasks';
            $this->ctaAction  = "switchTab('today')";
            $this->gradient   = 'from-amber-500 to-orange-500';
            $this->icon       = '📋';
            return;
        }

        // 5) Mood not logged today (purple)
        if ($needsMoodLog) {
            $this->actionType = 'mood';
            $this->headline   = 'Log your mood for today';
            $this->subtext    = 'A quick mood check helps track your overall wellness.';
            $this->nextUp     = $nextFrom([
                $nextUpcomingDose ? 'Next dose: ' . $nextUpcomingDose['med']->name . ' at ' . $nextUpcomingDose['scheduled']->format('g:i A') : null,
            ]);
            $this->ctaLabel   = 'Open Mood Tracker';
            $this->ctaAction  = "switchTab('today')";
            $this->gradient   = 'from-violet-500 to-purple-600';
            $this->icon       = '😊';
            return;
        }

        // 6) All done celebration (green)
        $this->actionType = 'done';
        $this->headline   = 'All caught up! Great job! 🎉';
        $this->subtext    = 'You\'ve completed all your tasks, medications, and vitals for today.';
        $this->nextUp     = $nextFrom([
            $nextUpcomingDose ? 'Next dose: ' . $nextUpcomingDose['med']->name . ' at ' . $nextUpcomingDose['scheduled']->format('g:i A') : null,
            'Enjoy your day and check back later.',
        ]);
        $this->ctaLabel   = 'Wellness Center';
        $this->ctaAction  = "window.location.href='" . route('elderly.wellness.index') . "'";
        $this->gradient   = 'from-emerald-500 to-green-600';
        $this->icon       = '🎉';
    }

    public function render(): View|Closure|string
    {
        return view('components.elderly-hero-action');
    }
}
