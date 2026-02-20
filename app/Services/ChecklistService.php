<?php

namespace App\Services;

use App\Models\Checklist;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ChecklistService
{
    /**
     * Add a checklist item
     */
    public function addChecklistItem(array $data): Checklist
    {
        return Checklist::create([
            'elderly_id' => $data['elderly_id'],
            'caregiver_id' => $data['caregiver_id'],
            'task' => $data['task'],
            'category' => $data['category'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'is_completed' => false,
        ]);
    }

    /**
     * Update checklist item
     */
    public function updateChecklistItem(Checklist $checklist, array $data): Checklist
    {
        $checklist->update([
            'task' => $data['task'] ?? $checklist->task,
            'category' => $data['category'] ?? $checklist->category,
            'due_date' => $data['due_date'] ?? $checklist->due_date,
        ]);

        return $checklist->fresh();
    }

    /**
     * Mark checklist item as completed
     */
    public function markAsCompleted(int $checklistId): Checklist
    {
        $checklist = Checklist::findOrFail($checklistId);
        $checklist->update([
            'is_completed' => true,
            'completed_at' => Carbon::now(),
        ]);

        return $checklist->fresh();
    }

    /**
     * Mark checklist item as not completed
     */
    public function markAsNotCompleted(int $checklistId): Checklist
    {
        $checklist = Checklist::findOrFail($checklistId);
        $checklist->update([
            'is_completed' => false,
            'completed_at' => null,
        ]);

        return $checklist->fresh();
    }

    /**
     * Get all checklist items for elderly
     */
    public function getChecklistForElderly(int $elderlyProfileId): Collection
    {
        return Checklist::where('elderly_id', $elderlyProfileId)
            ->orderBy('is_completed', 'asc')
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get today's checklist items
     */
    public function getTodaysChecklist(int $elderlyProfileId): Collection
    {
        return Checklist::where('elderly_id', $elderlyProfileId)
            ->whereDate('due_date', Carbon::today())
            ->orderBy('is_completed', 'asc')
            ->get();
    }

    /**
     * Get overdue checklist items
     */
    public function getOverdueItems(int $elderlyProfileId): Collection
    {
        return Checklist::where('elderly_id', $elderlyProfileId)
            ->where('is_completed', false)
            ->whereDate('due_date', '<', Carbon::today())
            ->get();
    }

    /**
     * Delete checklist item
     */
    public function deleteChecklistItem(int $checklistId): bool
    {
        $checklist = Checklist::findOrFail($checklistId);
        return $checklist->delete();
    }

    /**
     * Get completion rate for date range
     */
    public function getCompletionRate(int $elderlyProfileId, Carbon $startDate, Carbon $endDate): array
    {
        $totalItems = Checklist::where('elderly_id', $elderlyProfileId)
            ->whereBetween('due_date', [$startDate, $endDate])
            ->count();

        $completedItems = Checklist::where('elderly_id', $elderlyProfileId)
            ->whereBetween('due_date', [$startDate, $endDate])
            ->where('is_completed', true)
            ->count();

        $completionRate = $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 0;

        return [
            'total' => $totalItems,
            'completed' => $completedItems,
            'pending' => $totalItems - $completedItems,
            'completion_rate' => round($completionRate, 2),
        ];
    }
}
