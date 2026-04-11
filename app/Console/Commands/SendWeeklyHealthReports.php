<?php

namespace App\Console\Commands;

use App\Mail\WeeklyHealthReport;
use App\Models\Checklist;
use App\Models\UserProfile;
use App\Services\HealthAnalyticsService;
use App\Services\MedicationAdherenceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendWeeklyHealthReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send-weekly-health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and email weekly PDF health reports to caregivers';

    /**
     * Execute the console command.
     */
    public function handle(HealthAnalyticsService $analyticsService, MedicationAdherenceService $adherenceService): int
    {
        $caregivers = UserProfile::where('user_type', 'caregiver')
            ->with(['user', 'elderlyPatients.user'])
            ->get();

        if ($caregivers->isEmpty()) {
            $this->line('No caregivers found.');
            return Command::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($caregivers as $caregiver) {
            try {
                $caregiverUser = $caregiver->user;
                if (!$caregiverUser || empty($caregiverUser->email)) {
                    continue;
                }

                foreach ($caregiver->elderlyPatients as $elderly) {
                    try {
                        $elderlyUser = $elderly->user;
                        if (!$elderlyUser) {
                            continue;
                        }

                        $periods = ['7days' => Carbon::now()->subDays(7)];
                        $analyticsData = $analyticsService->getAnalyticsData($elderly->id, $periods);
                        $health = $analyticsService->calculateHealthScore($analyticsData);
                        $readings = $analyticsService->getReadingCounts($elderly->id);
                        $medicationSummary = $adherenceService->weekSummary($elderly);
                        $taskSummary = $this->taskSummary($elderly->id);

                        $pdfBinary = Pdf::loadView('caregiver.analytics_pdf', [
                            'elderly' => $elderly,
                            'elderlyUser' => $elderlyUser,
                            'analyticsData' => $analyticsData,
                            'healthScore' => $health['score'],
                            'healthLabel' => $health['label'],
                            'healthFactors' => $health['factors'],
                            'totalReadings' => $readings['total'],
                            'readingsThisWeek' => $readings['thisWeek'],
                            'medicationSummary' => $medicationSummary,
                            'taskSummary' => $taskSummary,
                        ])->output();

                        $filename = 'SilverCare_Weekly_Health_Report_'
                            . Str::slug($elderlyUser->name ?: 'patient')
                            . '_'
                            . now()->format('Y-m-d')
                            . '.pdf';

                        Mail::to($caregiverUser->email)->send(new WeeklyHealthReport(
                            caregiverUser: $caregiverUser,
                            elderlyProfile: $elderly,
                            pdfBinary: $pdfBinary,
                            filename: $filename,
                        ));

                        $sent++;
                    } catch (\Throwable $exception) {
                        $failed++;
                        Log::warning('Failed to send weekly health report for patient', [
                            'caregiver_profile_id' => $caregiver->id,
                            'elderly_profile_id' => $elderly->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            } catch (\Throwable $exception) {
                $failed++;
                Log::warning('Failed weekly report processing for caregiver', [
                    'caregiver_profile_id' => $caregiver->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info("Weekly reports sent: {$sent}; failed: {$failed}");

        return Command::SUCCESS;
    }

    private function taskSummary(int $elderlyId): array
    {
        $start = Carbon::today()->subDays(6);
        $end = Carbon::today();

        $tasks = Checklist::where('elderly_id', $elderlyId)
            ->whereBetween('due_date', [$start, $end])
            ->get();

        $total = $tasks->count();
        $completed = $tasks->where('is_completed', true)->count();
        $overdue = $tasks->where('is_completed', false)
            ->filter(fn ($task) => $task->due_date->isPast() && !$task->due_date->isToday())
            ->count();
        $dueToday = $tasks->filter(fn ($task) => $task->due_date->isToday() && !$task->is_completed)->count();

        $byCategory = $tasks->groupBy('category')->map(function ($items, $category) {
            $catTotal = $items->count();
            $catCompleted = $items->where('is_completed', true)->count();

            return [
                'category' => $category,
                'total' => $catTotal,
                'completed' => $catCompleted,
                'rate' => $catTotal > 0 ? round(($catCompleted / $catTotal) * 100) : 0,
            ];
        })->values();

        return [
            'total' => $total,
            'completed' => $completed,
            'completionRate' => $total > 0 ? round(($completed / $total) * 100) : null,
            'overdue' => $overdue,
            'dueToday' => $dueToday,
            'byCategory' => $byCategory,
        ];
    }
}
