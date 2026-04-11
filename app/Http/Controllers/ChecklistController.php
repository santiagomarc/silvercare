<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesElderlyPatient;
use App\Http\Requests\StoreChecklistRequest;
use App\Http\Requests\UpdateChecklistRequest;
use App\Models\Checklist;
use App\Services\ChecklistService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class ChecklistController extends Controller
{
    use ResolvesElderlyPatient;

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
        $elderlyPatients = $this->caregiverPatients($caregiver);
        $selectedElderly = $this->resolveSelectedPatient($elderlyPatients, request()->integer('elderly'));

        if (!$selectedElderly) {
            return redirect()->route('caregiver.dashboard')->with('error', 'No elderly profile associated.');
        }

        // Fetch tasks ordered by due_date and due_time
        $checklists = $this->checklistService->getChecklistForElderly($selectedElderly->id);

        return view('caregiver.checklists.index', compact('checklists', 'elderlyPatients', 'selectedElderly'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Checklist::class);

        $caregiver = Auth::user()->profile;
        $elderlyPatients = $this->caregiverPatients($caregiver);
        $selectedElderly = $this->resolveSelectedPatient($elderlyPatients, request()->integer('elderly'));

        if (!$selectedElderly) {
            return redirect()->route('caregiver.dashboard')->with('error', 'No elderly profile associated.');
        }

        return view('caregiver.checklists.create', compact('elderlyPatients', 'selectedElderly'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreChecklistRequest $request)
    {
        $this->authorize('create', Checklist::class);

        $caregiver = Auth::user()->profile;
        $elderlyPatients = $this->caregiverPatients($caregiver);
        $selectedElderly = $this->resolveSelectedPatient($elderlyPatients, $request->integer('elderly_id'));

        if (!$selectedElderly) {
            return redirect()->route('caregiver.dashboard')->with('error', 'No elderly profile associated.');
        }

        $this->checklistService->addChecklistItem([
            'elderly_id' => $selectedElderly->id,
            'caregiver_id' => $caregiver->id,
            ...$request->validated(),
            'is_completed' => false,
        ]);

        return redirect()->route('caregiver.checklists.index', ['elderly' => $selectedElderly->id])
            ->with('success', 'Task added successfully.');
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

        $caregiver = Auth::user()->profile;
        $elderlyPatients = $this->caregiverPatients($caregiver);
        $selectedElderly = $this->resolveSelectedPatient($elderlyPatients, (int) $checklist->elderly_id);

        return view('caregiver.checklists.edit', compact('checklist', 'elderlyPatients', 'selectedElderly'));
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

        $selectedElderlyId = $request->integer('elderly_id') ?: $checklist->elderly_id;

        return redirect()->route('caregiver.checklists.index', ['elderly' => $selectedElderlyId])
            ->with('success', 'Task updated successfully.');
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

        $selectedElderlyId = request()->integer('elderly_id') ?: $checklist->elderly_id;

        return redirect()->route('caregiver.checklists.index', ['elderly' => $selectedElderlyId])
            ->with('success', 'Task status updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Checklist $checklist)
    {
        $this->authorize('delete', $checklist);

        $selectedElderlyId = request()->integer('elderly_id') ?: $checklist->elderly_id;

        $this->checklistService->deleteChecklistItem($checklist->id);

        return redirect()->route('caregiver.checklists.index', ['elderly' => $selectedElderlyId])
            ->with('success', 'Task deleted successfully.');
    }

}
