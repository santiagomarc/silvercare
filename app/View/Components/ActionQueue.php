<?php

namespace App\View\Components;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class ActionQueue extends Component
{
    public array $steps = [];
    public int $initialTotal = 0;

    /**
     * Create a new component instance.
     */
    public function __construct(
        Collection $medications,
        Collection $medicationLogs,
        array $vitalsData,
        Collection $checklists,
        bool $moodRecorded = false,
    ) {
        $this->steps = $this->buildSteps($medications, $medicationLogs, $vitalsData, $checklists, $moodRecorded);
        $this->initialTotal = count($this->steps);
    }

    /**
     * Build a one-at-a-time queue of actions for today.
     */
    private function buildSteps(
        Collection $medications,
        Collection $medicationLogs,
        array $vitalsData,
        Collection $checklists,
        bool $moodRecorded,
    ): array {
        $today = Carbon::today();
        $now = Carbon::now();

        $overdueMeds = [];
        $activeMeds = [];

        foreach ($medications as $medication) {
            foreach ($medication->scheduleTimesForDate($today) as $time) {
                $logKey = $medication->id . '_' . Carbon::parse($time)->format('H:i');
                $log = $medicationLogs->get($logKey);

                if ($log && $log->is_taken) {
                    continue;
                }

                $scheduled = Carbon::parse($today->format('Y-m-d') . ' ' . $time);
                $windowStart = $scheduled->copy()->subMinutes(60);
                $windowEnd = $scheduled->copy()->addMinutes(60);

                $item = [
                    'id' => 'med-' . $medication->id . '-' . str_replace(':', '', $time),
                    'type' => 'medication',
                    'medication_id' => $medication->id,
                    'time' => $time,
                    'title' => 'Take ' . $medication->name,
                    'subtitle' => trim(($medication->dosage ? $medication->dosage . ' ' . ($medication->dosage_unit ?? '') . ' · ' : '') . 'Scheduled ' . $scheduled->format('g:i A')),
                    'tag' => 'Medication',
                    'priority' => 'active',
                ];

                if ($now->gt($windowEnd)) {
                    $item['tag'] = 'Overdue';
                    $item['priority'] = 'overdue';
                    $overdueMeds[] = array_merge($item, ['scheduled' => $scheduled->toIso8601String()]);
                } elseif ($now->between($windowStart, $windowEnd)) {
                    $activeMeds[] = array_merge($item, ['scheduled' => $scheduled->toIso8601String()]);
                }
            }
        }

        usort($overdueMeds, fn ($a, $b) => strcmp($a['scheduled'], $b['scheduled']));
        usort($activeMeds, fn ($a, $b) => strcmp($a['scheduled'], $b['scheduled']));

        $steps = array_merge($overdueMeds, $activeMeds);

        $vitalOrder = ['blood_pressure', 'sugar_level', 'temperature', 'heart_rate'];
        $vitalLabels = [
            'blood_pressure' => 'Blood Pressure',
            'sugar_level' => 'Sugar Level',
            'temperature' => 'Temperature',
            'heart_rate' => 'Heart Rate',
        ];
        $vitalRoutes = [
            'blood_pressure' => route('elderly.vitals.blood_pressure'),
            'sugar_level' => route('elderly.vitals.sugar_level'),
            'temperature' => route('elderly.vitals.temperature'),
            'heart_rate' => route('elderly.vitals.heart_rate'),
        ];

        foreach ($vitalOrder as $vitalType) {
            $recorded = (bool) ($vitalsData[$vitalType]['recorded'] ?? false);
            if ($recorded) {
                continue;
            }

            $steps[] = [
                'id' => 'vital-' . $vitalType,
                'type' => 'vital',
                'vital_type' => $vitalType,
                'title' => 'Record ' . $vitalLabels[$vitalType],
                'subtitle' => 'Quick check to keep your daily health log complete.',
                'tag' => 'Vital',
                'route' => $vitalRoutes[$vitalType],
            ];
        }

        $incompleteTasks = $checklists
            ->where('is_completed', false)
            ->sortBy(fn ($task) => ($task->due_time ?? '23:59') . '|' . ($task->task ?? ''))
            ->values();

        foreach ($incompleteTasks as $task) {
            $taskSubtitle = $task->description ?: 'Daily checklist item.';
            if ($task->due_time) {
                $taskSubtitle = 'Due ' . Carbon::parse($task->due_time)->format('g:i A') . ' · ' . $taskSubtitle;
            }

            $steps[] = [
                'id' => 'task-' . $task->id,
                'type' => 'task',
                'task_id' => $task->id,
                'title' => $task->task,
                'subtitle' => $taskSubtitle,
                'tag' => 'Task',
            ];
        }

        if (! $moodRecorded) {
            $steps[] = [
                'id' => 'mood-today',
                'type' => 'mood',
                'title' => "Log today's mood",
                'subtitle' => 'A quick mood check helps track your wellness pattern.',
                'tag' => 'Mood',
            ];
        }

        return array_values($steps);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.action-queue');
    }
}
