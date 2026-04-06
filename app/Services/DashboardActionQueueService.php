<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardActionQueueService
{
    /**
     * @param array<string, mixed> $vitalsData
     * @return array<int, array<string, mixed>>
     */
    public function buildSteps(
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
        $upcomingMeds = [];

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

                $base = [
                    'id' => 'med-' . $medication->id . '-' . str_replace(':', '', $time),
                    'type' => 'medication',
                    'medication_id' => $medication->id,
                    'time' => $time,
                    'title' => 'Take ' . $medication->name,
                    'subtitle' => trim(($medication->dosage ? $medication->dosage . ' ' . ($medication->dosage_unit ?? '') . ' · ' : '') . 'Scheduled ' . $scheduled->format('g:i A')),
                    'scheduled' => $scheduled->toIso8601String(),
                ];

                if ($now->gt($windowEnd)) {
                    $overdueMeds[] = array_merge($base, [
                        'tag' => 'Overdue',
                        'priority' => 'overdue',
                        'gradient' => 'from-red-600 to-rose-700',
                        'gradient_style' => 'linear-gradient(140deg, rgba(185, 28, 28, 0.96) 0%, rgba(225, 29, 72, 0.9) 52%, rgba(251, 113, 133, 0.82) 100%)',
                        'icon' => '🚨',
                    ]);
                    continue;
                }

                if ($now->between($windowStart, $windowEnd)) {
                    $activeMeds[] = array_merge($base, [
                        'tag' => 'Medication',
                        'priority' => 'active',
                        'gradient' => 'from-sky-500 to-blue-700',
                        'gradient_style' => 'linear-gradient(140deg, rgba(14, 165, 233, 0.95) 0%, rgba(59, 130, 246, 0.9) 54%, rgba(129, 140, 248, 0.82) 100%)',
                        'icon' => '💊',
                    ]);
                    continue;
                }

                if ($scheduled->isFuture()) {
                    $upcomingMeds[] = array_merge($base, [
                        'tag' => 'Upcoming',
                        'priority' => 'upcoming',
                        'gradient' => 'from-indigo-500 to-blue-700',
                        'gradient_style' => 'linear-gradient(140deg, rgba(99, 102, 241, 0.94) 0%, rgba(79, 70, 229, 0.9) 54%, rgba(59, 130, 246, 0.82) 100%)',
                        'icon' => '⏰',
                    ]);
                }
            }
        }

        usort($overdueMeds, fn ($a, $b) => strcmp((string) $a['scheduled'], (string) $b['scheduled']));
        usort($activeMeds, fn ($a, $b) => strcmp((string) $a['scheduled'], (string) $b['scheduled']));
        usort($upcomingMeds, fn ($a, $b) => strcmp((string) $a['scheduled'], (string) $b['scheduled']));

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
                'gradient' => 'from-teal-600 to-cyan-700',
                'gradient_style' => 'linear-gradient(140deg, rgba(15, 118, 110, 0.94) 0%, rgba(14, 116, 144, 0.9) 54%, rgba(6, 182, 212, 0.82) 100%)',
                'icon' => '🩺',
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
                'gradient' => 'from-amber-600 to-orange-700',
                'gradient_style' => 'linear-gradient(140deg, rgba(217, 119, 6, 0.94) 0%, rgba(234, 88, 12, 0.9) 54%, rgba(251, 146, 60, 0.82) 100%)',
                'icon' => '📋',
            ];
        }

        if (! $moodRecorded) {
            $steps[] = [
                'id' => 'mood-today',
                'type' => 'mood',
                'title' => "Log today's mood",
                'subtitle' => 'A quick mood check helps track your wellness pattern.',
                'tag' => 'Mood',
                'gradient' => 'from-violet-600 to-purple-700',
                'gradient_style' => 'linear-gradient(140deg, rgba(124, 58, 237, 0.94) 0%, rgba(109, 40, 217, 0.9) 54%, rgba(167, 139, 250, 0.82) 100%)',
                'icon' => '😊',
            ];
        }

        if (empty($steps)) {
            return [[
                'id' => 'done',
                'type' => 'done',
                'title' => 'All caught up! Great job! 🎉',
                'subtitle' => "You've completed all your tasks, medications, and vitals for today.",
                'tag' => 'Done',
                'gradient' => 'from-emerald-600 to-green-700',
                'gradient_style' => 'linear-gradient(140deg, rgba(5, 150, 105, 0.94) 0%, rgba(22, 163, 74, 0.9) 54%, rgba(52, 211, 153, 0.82) 100%)',
                'icon' => '🎉',
            ]];
        }

        return array_values(array_merge($steps, $upcomingMeds));
    }
}