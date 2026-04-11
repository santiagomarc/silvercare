<?php

namespace App\Console\Commands;

use App\Models\Checklist;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * M1 FIX: RecycleRecurringChecklists
 *
 * Problem:
 *   Recurring tasks (is_recurring = true) only have a single row in the checklists
 *   table with a fixed due_date. Once completed, they stay "completed" forever.
 *   getTodaysChecklist() filters by due_date = today, so a weekly task created on
 *   Monday with due_date = Monday never appears again on Tuesday+ in its recurring period.
 *
 * Solution:
 *   At midnight each day, find every recurring checklist whose due_date is in the past
 *   and for which no copy already exists for today (or the next due_date). Clone the row
 *   with:
 *     - is_completed  = false
 *     - completed_at  = null
 *     - due_date      = next occurrence based on frequency
 *
 *   Supported frequencies: daily, weekly, monthly (default: daily).
 *
 * History preservation:
 *   The original completed row is left intact — only a fresh copy is inserted.
 *   This ensures caregiver analytics and completion-rate reports remain accurate.
 *
 * Command: php artisan checklists:recycle-recurring
 */
class RecycleRecurringChecklists extends Command
{
    protected $signature = 'checklists:recycle-recurring
                            {--dry-run : Preview what would be created without writing to DB}';

    protected $description = 'Create today\'s copies of all due recurring checklist tasks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $today    = Carbon::today();
        $now      = Carbon::now();

        $this->info("🔄 Recycling recurring checklists for {$today->toDateString()}...");

        if ($isDryRun) {
            $this->warn('DRY RUN MODE — no rows will be written.');
        }

        // Find all recurring checklists whose due_date is strictly before today.
        // We exclude today's rows (they may already be pending) and future rows.
        $candidates = Checklist::where('is_recurring', true)
            ->whereDate('due_date', '<', $today)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('  No recurring checklists due for recycling. All up to date.');
            return Command::SUCCESS;
        }

        $created  = 0;
        $skipped  = 0;
        $errors   = 0;

        foreach ($candidates as $checklist) {
            try {
                $nextDueDate = $this->computeNextDueDate($checklist->due_date, $checklist->frequency);

                // Skip if next due date is in the future (e.g., a weekly task recycled
                // mid-week should not appear until its scheduled day).
                if ($nextDueDate->greaterThan($today)) {
                    $this->line("  ⏭  [{$checklist->id}] \"{$checklist->task}\" — next due {$nextDueDate->toDateString()}, skipping.");
                    $skipped++;
                    continue;
                }

                // Idempotency: do not create a duplicate if one already exists for the same
                // elderly user, task content, and due_date (handles re-runs / overlapping schedules).
                $alreadyExists = Checklist::where('elderly_id', $checklist->elderly_id)
                    ->where('task', $checklist->task)
                    ->whereDate('due_date', $nextDueDate)
                    ->exists();

                if ($alreadyExists) {
                    $this->line("  ✓  [{$checklist->id}] \"{$checklist->task}\" — copy for {$nextDueDate->toDateString()} already exists.");
                    $skipped++;
                    continue;
                }

                if (!$isDryRun) {
                    DB::transaction(function () use ($checklist, $nextDueDate, $now) {
                        Checklist::create([
                            'elderly_id'   => $checklist->elderly_id,
                            'caregiver_id' => $checklist->caregiver_id,
                            'task'         => $checklist->task,
                            'description'  => $checklist->description,
                            'category'     => $checklist->category,
                            'due_date'     => $nextDueDate,
                            'due_time'     => $checklist->due_time,
                            'priority'     => $checklist->priority,
                            'notes'        => $checklist->notes,
                            'frequency'    => $checklist->frequency,
                            'is_recurring' => true,
                            'is_completed' => false,
                            'completed_at' => null,
                        ]);
                    });
                }

                $this->info("  ✅ [{$checklist->id}] \"{$checklist->task}\" — cloned for {$nextDueDate->toDateString()}.");
                $created++;

            } catch (\Throwable $e) {
                $this->error("  ❌ [{$checklist->id}] Failed: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Finished: {$created} created, {$skipped} skipped, {$errors} errors.");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Compute the next due date for a recurring checklist based on its frequency.
     *
     * Supported values (case-insensitive): daily, weekly, monthly, biweekly, yearly.
     * Falls back to daily for any unknown/null frequency string.
     */
    private function computeNextDueDate(Carbon $lastDueDate, ?string $frequency): Carbon
    {
        $freq = strtolower(trim($frequency ?? ''));

        return match ($freq) {
            'weekly'    => $lastDueDate->copy()->addWeek(),
            'biweekly'  => $lastDueDate->copy()->addWeeks(2),
            'monthly'   => $lastDueDate->copy()->addMonth(),
            'yearly'    => $lastDueDate->copy()->addYear(),
            default     => $lastDueDate->copy()->addDay(),  // 'daily' + unknown
        };
    }
}
