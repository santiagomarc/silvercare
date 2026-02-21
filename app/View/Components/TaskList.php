<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TaskList extends Component
{
    public $checklists;
    public $completedCount;
    public $totalCount;
    public $progress;

    /**
     * Create a new component instance.
     */
    public function __construct($checklists, $completedCount, $totalCount)
    {
        $this->checklists = $checklists;
        $this->completedCount = $completedCount;
        $this->totalCount = $totalCount;
        $this->progress = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.task-list');
    }
}
