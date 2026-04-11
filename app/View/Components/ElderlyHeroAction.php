<?php

namespace App\View\Components;

use App\Services\DashboardActionQueueService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Collection;

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
    public string $actionType;   // 'medication' | 'vital' | 'task' | 'mood' | 'done'
    public string $headline;
    public string $subtext;
    public string $gradient;     // Tailwind gradient classes
    public string $gradientStyle;
    public string $icon;
    public string $tag;
    public int    $overallProgress;
    public int    $initialTotal;

    /** @var array<int, array<string, mixed>> */
    public array $steps = [];

    public function __construct(
        Collection $medications,
        Collection $medicationLogs,
        array      $vitalsData,
        Collection $checklists,
        bool       $moodRecorded = false,
        int        $dailyGoalsProgress = 0,
    ) {
        $this->overallProgress = $dailyGoalsProgress;
        $actionQueueService = app(DashboardActionQueueService::class);
        $this->steps = $actionQueueService->buildSteps(
            $medications,
            $medicationLogs,
            $vitalsData,
            $checklists,
            $moodRecorded,
        );

        $this->initialTotal = count($this->steps);

        $current = $this->steps[0] ?? [
            'type' => 'done',
            'title' => 'All caught up! Great job! 🎉',
            'subtitle' => "You've completed all your tasks, medications, and vitals for today.",
            'gradient' => 'from-emerald-600 to-green-700',
            'gradient_style' => 'linear-gradient(135deg, #059669 0%, #15803d 100%)',
            'icon' => '🎉',
            'tag' => 'Done',
        ];

        $this->actionType = (string) ($current['type'] ?? 'done');
        $this->headline = (string) ($current['title'] ?? 'All caught up! Great job! 🎉');
        $this->subtext = (string) ($current['subtitle'] ?? "You've completed all your tasks, medications, and vitals for today.");
        $this->gradient = (string) ($current['gradient'] ?? 'from-emerald-600 to-green-700');
        $this->gradientStyle = (string) ($current['gradient_style'] ?? 'linear-gradient(135deg, #059669 0%, #15803d 100%)');
        $this->icon = (string) ($current['icon'] ?? '🎉');
        $this->tag = (string) ($current['tag'] ?? 'Done');
    }

    public function render(): View|Closure|string
    {
        return view('components.elderly-hero-action');
    }
}
