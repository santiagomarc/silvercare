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
        protected \App\Services\ClinicalInsightService $insightService,
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
            '365days' => Carbon::now()->subDays(365),
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

        $briefing = $this->insightService->getDailyBriefing($elderly);

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
            'taskSummary',
            'briefing',
            'sourceAttribution',
            'doseLateness',
            'alertHistory',
            'reportGeneratedAt'
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

        $briefing = $this->insightService->getDailyBriefing($elderly);

        // M3: provenance, dose lateness and alert history for the report.
        // A clinician reading this needs to know which numbers the patient
        // typed, which a device supplied, and how current any of it is.
        $sourceAttribution = \App\Models\HealthMetric::where('elderly_id', $elderly->id)
            ->where('measured_at', '>=', now()->subDays(7))
            ->select('source', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('source')
            ->pluck('total', 'source');

        $doseLateness = \App\Models\DoseInstance::where('elderly_id', $elderly->id)
            ->where('state', 'taken_late')
            ->where('scheduled_at_utc', '>=', now()->subDays(7))
            ->with('medication:id,name')
            ->get()
            ->groupBy('medication_id')
            ->map(fn ($rows) => [
                'name' => $rows->first()->medication?->name ?? 'Medication',
                'late_count' => $rows->count(),
                'average_minutes_late' => (int) round(
                    $rows->avg(fn ($r) => $r->taken_at && $r->scheduled_at_utc
                        ? $r->scheduled_at_utc->diffInMinutes($r->taken_at)
                        : 0)
                ),
            ])
            ->values();

        $alertHistory = \App\Models\Alert::where('elderly_id', $elderly->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        $reportGeneratedAt = now();

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
            'taskSummary',
            'briefing',
            'sourceAttribution',
            'doseLateness',
            'alertHistory',
            'reportGeneratedAt'
        ));

        $filename = 'SilverCare_Health_Report_' . ($elderlyUser->name ?? 'Patient') . '_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * M2 — machine-readable clinical summary for a patient.
     *
     * Everything here comes from ClinicalInsightService's deterministic rules.
     * The `as_of` timestamp and per-vital freshness are part of the payload on
     * purpose: a caregiver reading "blood pressure normal" needs to know
     * whether that reading is from this morning or from nine days ago.
     */
    public function clinicalSummary(\Illuminate\Http\Request $request, \App\Models\UserProfile $patient): \Illuminate\Http\JsonResponse
    {
        $caregiver = \Illuminate\Support\Facades\Auth::user()->profile;

        if (! $caregiver || ! $caregiver->isCaregiver() || $patient->caregiver_id !== $caregiver->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $briefing = $this->insightService->getDailyBriefing($patient);

        $sources = \App\Models\HealthMetric::where('elderly_id', $patient->id)
            ->where('measured_at', '>=', now()->subDays(7))
            ->select('source', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('source')
            ->pluck('total', 'source');

        return response()->json([
            'success' => true,
            'as_of' => $briefing['generated_at'] ?? now()->toISOString(),
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->user?->name,
                'timezone' => $patient->timezone,
            ],
            'risk' => $briefing['risk'] ?? null,
            'medication_adherence' => $briefing['medication_adherence'] ?? null,
            'freshness' => $briefing['freshness'] ?? [],
            'open_alerts_count' => $briefing['open_alerts_count'] ?? 0,
            'highlights' => $briefing['highlights'] ?? [],
            'today_checkin' => $briefing['today_checkin'],
            // Provenance: manual entry, Google Fit, voice, camera OCR, offline
            // sync. A caregiver should know which readings the patient typed and
            // which a device supplied.
            'source_attribution' => $sources,
            'disclaimer' => 'Computed by SilverCare\'s rules engine from recorded data. Not a medical assessment.',
        ]);
    }
}

