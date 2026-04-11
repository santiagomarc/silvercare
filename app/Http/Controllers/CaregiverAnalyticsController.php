<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesElderlyPatient;
use App\Models\Checklist;
use App\Services\HealthAnalyticsService;
use App\Services\MedicationAdherenceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CaregiverAnalyticsController extends Controller
{
    use ResolvesElderlyPatient;

    public function __construct(
        protected HealthAnalyticsService $analyticsService,
        protected MedicationAdherenceService $adherenceService,
    ) {
    }

    public function index()
    {
        $caregiver = Auth::user()->profile;
        
        if (!$caregiver) {
            return redirect()->route('profile.complete');
        }

        $elderlyPatients = $this->caregiverPatients($caregiver);
        $elderly = $this->resolveSelectedPatient($elderlyPatients, request()->integer('elderly'));
        $selectedElderlyId = $elderly?->id;

        if (!$elderly) {
            return view('caregiver.analytics', [
                'elderly' => null,
                'elderlyPatients' => $elderlyPatients,
                'selectedElderlyId' => null,
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
        $medicationSummary = $this->adherenceService->weekSummary($elderly);
        
        // Task Summary (7 days)
        $taskSummary = $this->getTaskSummary($elderly);

        return view('caregiver.analytics', compact(
            'elderly', 
            'elderlyPatients',
            'selectedElderlyId',
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
    public function exportPdf(Request $request)
    {
        $caregiver = Auth::user()->profile;
        
        if (!$caregiver) {
            return redirect()->route('profile.complete');
        }

        $elderlyPatients = $this->caregiverPatients($caregiver);
        $elderly = $this->resolveSelectedPatient($elderlyPatients, $request->integer('elderly'));

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

        $medicationSummary = $this->adherenceService->weekSummary($elderly);
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

