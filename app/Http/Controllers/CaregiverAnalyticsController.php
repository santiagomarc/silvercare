<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HealthMetric;
use App\Models\MedicationLog;
use App\Models\Checklist;
use App\Models\Medication;
use App\Services\HealthAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class CaregiverAnalyticsController extends Controller
{
    public function __construct(protected HealthAnalyticsService $analyticsService)
    {
    }

    public function index()
    {
        $caregiver = Auth::user()->profile;
        
        if (!$caregiver) {
            return redirect()->route('profile.complete');
        }

        $elderly = $caregiver->elderly;

        if (!$elderly) {
            return view('caregiver.analytics', [
                'elderly' => null,
                'elderlyUser' => null,
                'analyticsData' => [],
                'healthScore' => 0,
                'healthLabel' => 'No Data',
                'healthColor' => 'gray',
                'totalReadings' => 0,
                'readingsThisWeek' => 0,
                'medicationSummary' => [],
                'taskSummary' => [],
            ]);
        }

        $elderlyUser = $elderly->user;
        $elderlyId = $elderly->id;
        
        // Use shared analytics service
        $periods = [
            '7days'  => Carbon::now()->subDays(7),
            '30days' => Carbon::now()->subDays(30),
            '90days' => Carbon::now()->subDays(90),
        ];

        $analyticsData = $this->analyticsService->getAnalyticsData($elderlyId, $periods);
        $health = $this->analyticsService->calculateHealthScore($analyticsData);
        $readings = $this->analyticsService->getReadingCounts($elderlyId);

        $healthScore   = $health['score'];
        $healthLabel   = $health['label'];
        $healthColor   = $health['color'];
        $healthFactors = $health['factors'];
        $totalFactors  = $health['totalFactors'];
        $totalReadings    = $readings['total'];
        $readingsThisWeek = $readings['thisWeek'];

        // Medication Summary (7 days)
        $medicationSummary = $this->getMedicationSummary($elderly);
        
        // Task Summary (7 days)
        $taskSummary = $this->getTaskSummary($elderly);

        return view('caregiver.analytics', compact(
            'elderly', 
            'elderlyUser', 
            'analyticsData',
            'healthScore',
            'healthLabel',
            'healthColor',
            'healthFactors',
            'totalFactors',
            'totalReadings',
            'readingsThisWeek',
            'medicationSummary',
            'taskSummary'
        ));
    }

    private function getMedicationSummary($elderly)
    {
        $medications = $elderly->trackedMedications()->where('is_active', true)->get();
        $last7Days = Carbon::today()->subDays(6);
        
        // Pre-fetch ALL taken logs for the 7-day window in one query
        $medIds = $medications->pluck('id');
        $allLogs = MedicationLog::whereIn('medication_id', $medIds)
            ->whereDate('scheduled_time', '>=', $last7Days)
            ->whereDate('scheduled_time', '<=', Carbon::today())
            ->where('is_taken', true)
            ->get()
            ->groupBy(fn ($log) => $log->medication_id . '_' . $log->scheduled_time->format('Y-m-d'));

        $totalScheduled = 0;
        $totalTaken = 0;
        $lowStockCount = 0;
        $medDetails = [];
        
        foreach ($medications as $med) {
            $scheduled = 0;
            $taken = 0;
            
            for ($date = $last7Days->copy(); $date <= Carbon::today(); $date->addDay()) {
                $dayOfWeek = $date->format('l');
                if (in_array($dayOfWeek, $med->days_of_week ?? [])) {
                    $doseCount = count($med->times_of_day ?? []);
                    $scheduled += $doseCount;
                    
                    $key = $med->id . '_' . $date->format('Y-m-d');
                    $taken += ($allLogs->get($key)?->count() ?? 0);
                }
            }
            
            $totalScheduled += $scheduled;
            $totalTaken += $taken;
            
            if ($med->track_inventory && $med->current_stock <= ($med->low_stock_threshold ?? 5)) {
                $lowStockCount++;
            }
            
            $medDetails[] = [
                'name' => $med->name,
                'scheduled' => $scheduled,
                'taken' => $taken,
                'adherence' => $scheduled > 0 ? round(($taken / $scheduled) * 100) : null,
                'lowStock' => $med->track_inventory && $med->current_stock <= ($med->low_stock_threshold ?? 5),
                'stock' => $med->current_stock,
            ];
        }
        
        return [
            'totalMedications' => $medications->count(),
            'totalScheduled' => $totalScheduled,
            'totalTaken' => $totalTaken,
            'adherenceRate' => $totalScheduled > 0 ? round(($totalTaken / $totalScheduled) * 100) : null,
            'lowStockCount' => $lowStockCount,
            'medications' => $medDetails,
        ];
    }

    private function getTaskSummary($elderly)
    {
        $last7Days = Carbon::today()->subDays(6);
        
        $tasks = Checklist::where('elderly_id', $elderly->id)
            ->where('due_date', '>=', $last7Days)
            ->where('due_date', '<=', Carbon::today())
            ->get();
        
        $total = $tasks->count();
        $completed = $tasks->where('is_completed', true)->count();
        $overdue = $tasks->where('is_completed', false)
            ->filter(fn($t) => $t->due_date->isPast() && !$t->due_date->isToday())
            ->count();
        $dueToday = $tasks->filter(fn($t) => $t->due_date->isToday() && !$t->is_completed)->count();
        
        // By category
        $byCategory = $tasks->groupBy('category')->map(function($items, $category) {
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

    private function calculateTrend($metrics)
    {
        return $this->analyticsService->calculateTrend($metrics);
    }

    /**
     * Export analytics as PDF
     */
    public function exportPdf()
    {
        $caregiver = Auth::user()->profile;
        
        if (!$caregiver) {
            return redirect()->route('profile.complete');
        }

        $elderly = $caregiver->elderly;

        if (!$elderly) {
            return back()->with('error', 'No elder assigned to generate report.');
        }

        $elderlyUser = $elderly->user;
        $elderlyId = $elderly->id;

        $periods = ['7days' => Carbon::now()->subDays(7)];
        $analyticsData = $this->analyticsService->getAnalyticsData($elderlyId, $periods);
        $health = $this->analyticsService->calculateHealthScore($analyticsData);
        $readings = $this->analyticsService->getReadingCounts($elderlyId);

        $healthScore   = $health['score'];
        $healthLabel   = $health['label'];
        $healthFactors = $health['factors'];
        $totalReadings    = $readings['total'];
        $readingsThisWeek = $readings['thisWeek'];

        $medicationSummary = $this->getMedicationSummary($elderly);
        $taskSummary = $this->getTaskSummary($elderly);

        $pdf = Pdf::loadView('caregiver.analytics_pdf', compact(
            'elderly',
            'elderlyUser',
            'analyticsData',
            'healthScore',
            'healthLabel',
            'healthFactors',
            'totalReadings',
            'readingsThisWeek',
            'medicationSummary',
            'taskSummary'
        ));

        $filename = 'SilverCare_Health_Report_' . ($elderlyUser->name ?? 'Patient') . '_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}

