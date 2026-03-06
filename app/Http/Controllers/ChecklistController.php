<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChecklistRequest;
use App\Http\Requests\UpdateChecklistRequest;
use App\Models\Checklist;
use App\Services\ChecklistService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class ChecklistController extends Controller
{
    public function __construct(
        protected ChecklistService $checklistService,
        protected NotificationService $notificationService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Checklist::class);

        $caregiver = Auth::user()->profile;
        $elderly = $caregiver->elderly;

        if (!$elderly) {
            return redirect()->route('caregiver.dashboard')->with('error', 'No elderly profile associated.');
        }

        // Fetch tasks ordered by due_date and due_time
        $checklists = $this->checklistService->getChecklistForElderly($elderly->id);

        return view('caregiver.checklists.index', compact('checklists'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Checklist::class);

        return view('caregiver.checklists.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreChecklistRequest $request)
    {
        $this->authorize('create', Checklist::class);

        $caregiver = Auth::user()->profile;
        $elderly = $caregiver->elderly;

        if (!$elderly) {
            return redirect()->route('caregiver.dashboard')->with('error', 'No elderly profile associated.');
        }

        $this->checklistService->addChecklistItem([
            'elderly_id' => $elderly->id,
            'caregiver_id' => $caregiver->id,
            ...$request->validated(),
            'is_completed' => false,
        ]);

        return redirect()->route('caregiver.checklists.index')->with('success', 'Task added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Checklist $checklist)
    {
        $this->authorize('update', $checklist);

        return view('caregiver.checklists.edit', compact('checklist'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateChecklistRequest $request, Checklist $checklist)
    {
        $this->authorize('update', $checklist);

        // Handle completion toggle via hidden field
        $isCompleted = $request->boolean('is_completed');

        $this->checklistService->updateChecklistItem($checklist, [
            ...$request->validated(),
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted && !$checklist->is_completed ? now() : ($isCompleted ? $checklist->completed_at : null),
        ]);

        return redirect()->route('caregiver.checklists.index')->with('success', 'Task updated successfully.');
    }

    /**
     * Toggle completion status via AJAX or form.
     */
    public function toggleComplete(Checklist $checklist)
    {
        $this->authorize('update', $checklist);

        $newStatus = !$checklist->is_completed;

        $newStatus
            ? $this->checklistService->markAsCompleted($checklist->id)
            : $this->checklistService->markAsNotCompleted($checklist->id);

        // Create notification if task was completed
        if ($newStatus) {
            $this->notificationService->createTaskCompletedNotification(
                $checklist->elderly_id,
                $checklist->task,
                $checklist->category ?? 'General'
            );
        }

        return redirect()->route('caregiver.checklists.index')->with('success', 'Task status updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Checklist $checklist)
    {
        $this->authorize('delete', $checklist);

        $this->checklistService->deleteChecklistItem($checklist->id);

        return redirect()->route('caregiver.checklists.index')->with('success', 'Task deleted successfully.');
    }
}
