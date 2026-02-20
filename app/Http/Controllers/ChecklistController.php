<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ChecklistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $caregiver = Auth::user()->profile;
        $elderly = $caregiver->elderly;

        if (!$elderly) {
            return redirect()->route('caregiver.dashboard')->with('error', 'No elderly profile associated.');
        }

        // Fetch tasks ordered by due_date and due_time
        $checklists = $elderly->checklists()
            ->orderBy('due_date', 'asc')
            ->orderBy('due_time', 'asc')
            ->get();

        return view('caregiver.checklists.index', compact('checklists'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('caregiver.checklists.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'task' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|max:50',
            'due_date' => 'required|date',
            'due_time' => 'nullable|date_format:H:i',
        ]);

        $caregiver = Auth::user()->profile;
        $elderly = $caregiver->elderly;

        if (!$elderly) {
            return redirect()->route('caregiver.dashboard')->with('error', 'No elderly profile associated.');
        }

        Checklist::create([
            'elderly_id' => $elderly->id,
            'caregiver_id' => $caregiver->id,
            'task' => $request->task,
            'description' => $request->description,
            'category' => $request->category,
            'due_date' => $request->due_date,
            'due_time' => $request->due_time,
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
    public function edit(string $id)
    {
        $checklist = Checklist::findOrFail($id);
        if ($checklist->caregiver_id !== Auth::user()->profile->id) {
            abort(403);
        }
        return view('caregiver.checklists.edit', compact('checklist'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $checklist = Checklist::findOrFail($id);
        
        if ($checklist->caregiver_id !== Auth::user()->profile->id) {
            abort(403);
        }

        $request->validate([
            'task' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|max:50',
            'due_date' => 'required|date',
            'due_time' => 'nullable|date_format:H:i',
        ]);

        // Handle completion toggle via hidden field
        $isCompleted = $request->has('is_completed') && $request->is_completed == '1';
        
        $checklist->update([
            'task' => $request->task,
            'description' => $request->description,
            'category' => $request->category,
            'due_date' => $request->due_date,
            'due_time' => $request->due_time,
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted && !$checklist->is_completed ? now() : ($isCompleted ? $checklist->completed_at : null),
        ]);

        return redirect()->route('caregiver.checklists.index')->with('success', 'Task updated successfully.');
    }

    /**
     * Toggle completion status via AJAX or form.
     */
    public function toggleComplete(string $id)
    {
        $checklist = Checklist::findOrFail($id);
        
        if ($checklist->caregiver_id !== Auth::user()->profile->id) {
            abort(403);
        }

        $newStatus = !$checklist->is_completed;

        $checklist->update([
            'is_completed' => $newStatus,
            'completed_at' => $newStatus ? now() : null,
        ]);

        // Create notification if task was completed
        if ($newStatus) {
            app(NotificationService::class)->createTaskCompletedNotification(
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
    public function destroy(string $id)
    {
        $checklist = Checklist::findOrFail($id);
        if ($checklist->caregiver_id !== Auth::user()->profile->id) {
            abort(403);
        }
        $checklist->delete();
        return redirect()->route('caregiver.checklists.index')->with('success', 'Task deleted successfully.');
    }
}
