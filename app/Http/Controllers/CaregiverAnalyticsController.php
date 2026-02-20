<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HealthMetric;
use App\Models\MedicationLog;
use App\Models\Checklist;
use App\Models\Medication;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class CaregiverAnalyticsController extends Controller
{
    // Vital types configuration (same as HealthMetricController)
    private const VITAL_TYPES = [
        'blood_pressure' => [
            'name' => 'Blood Pressure',
            'unit' => 'mmHg',
            'icon' => '🩺',
            'color' => 'red',
        ],
        'heart_rate' => [
            'name' => 'Heart Rate',
            'unit' => 'bpm',
            'icon' => '❤️',
            'color' => 'rose',
        ],
        'sugar_level' => [
            'name' => 'Blood Sugar',
            'unit' => 'mg/dL',
            'icon' => '🍬',
            'color' => 'pink',
        ],
        'temperature' => [
            'name' => 'Temperature',
            'unit' => '°C',
            'icon' => '🌡️',
            'color' => 'orange',
        ],
    ];

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
        
        // Get vitals analytics data (same structure as elderly analytics)
        $periods = [
            '7days' => Carbon::now()->subDays(7),
            '30days' => Carbon::now()->subDays(30),
            '90days' => Carbon::now()->subDays(90),
        ];

        $analyticsData = [];
        $healthScore = 0;
        $healthFactors = [];
        $totalFactors = 0;

        foreach (self::VITAL_TYPES as $type => $config) {
            $data = [
                'config' => $config,
                'type' => $type,
            ];

            foreach ($periods as $periodKey => $startDate) {
                $metrics = HealthMetric::where('elderly_id', $elderlyId)
                    ->where('type', $type)
                    ->where('measured_at', '>=', $startDate)
                    ->orderBy('measured_at', 'asc')
                    ->get();

                $periodData = [
                    'count' => $metrics->count(),
                    'metrics' => $metrics,
                ];

                if ($type === 'blood_pressure') {
                    $systolic = [];
                    $diastolic = [];
                    foreach ($metrics as $metric) {
                        if ($metric->value_text && preg_match('/^(\d+)\/(\d+)$/', $metric->value_text, $matches)) {
                            $systolic[] = intval($matches[1]);
                            $diastolic[] = intval($matches[2]);
                        }
                    }
                    if (!empty($systolic)) {
                        $periodData['systolic_avg'] = round(array_sum($systolic) / count($systolic), 1);
                        $periodData['systolic_min'] = min($systolic);
                        $periodData['systolic_max'] = max($systolic);
                        $periodData['diastolic_avg'] = round(array_sum($diastolic) / count($diastolic), 1);
                        $periodData['diastolic_min'] = min($diastolic);
                        $periodData['diastolic_max'] = max($diastolic);
                    }
                } else {
                    if ($metrics->isNotEmpty()) {
                        $values = $metrics->pluck('value')->map(fn($v) => floatval($v));
                        $periodData['avg'] = round($values->avg(), 1);
                        $periodData['min'] = $values->min();
                        $periodData['max'] = $values->max();
                        $periodData['trend'] = $this->calculateTrend($metrics);
                    }
                }

                $data[$periodKey] = $periodData;
            }

            $analyticsData[$type] = $data;
            
            // Calculate health score contribution
            if (($data['7days']['count'] ?? 0) > 0) {
                $totalFactors++;
                $score = 0;
                $status = 'unknown';
                
                if ($type === 'blood_pressure') {
                    $sys = $data['7days']['systolic_avg'] ?? 120;
                    $dia = $data['7days']['diastolic_avg'] ?? 80;
                    if ($sys < 120 && $dia < 80) { $score = 100; $status = 'Optimal'; }
                    elseif ($sys < 130 && $dia < 85) { $score = 85; $status = 'Normal'; }
                    elseif ($sys < 140 && $dia < 90) { $score = 70; $status = 'Elevated'; }
                    else { $score = 50; $status = 'High'; }
                } elseif ($type === 'heart_rate') {
                    $hr = $data['7days']['avg'] ?? 72;
                    if ($hr >= 60 && $hr <= 100) { $score = 100; $status = 'Optimal'; }
                    elseif ($hr >= 50 && $hr <= 110) { $score = 80; $status = 'Normal'; }
                    else { $score = 60; $status = 'Attention'; }
                } elseif ($type === 'temperature') {
                    $temp = $data['7days']['avg'] ?? 36.5;
                    if ($temp >= 36.1 && $temp <= 37.2) { $score = 100; $status = 'Normal'; }
                    elseif ($temp >= 35.5 && $temp <= 37.8) { $score = 75; $status = 'Mild'; }
                    else { $score = 50; $status = 'Attention'; }
                } elseif ($type === 'sugar_level') {
                    $sugar = $data['7days']['avg'] ?? 100;
                    if ($sugar >= 70 && $sugar <= 100) { $score = 100; $status = 'Optimal'; }
                    elseif ($sugar >= 60 && $sugar <= 125) { $score = 80; $status = 'Normal'; }
                    else { $score = 60; $status = 'Attention'; }
                }
                
                $healthScore += $score;
                $healthFactors[$type] = ['score' => $score, 'status' => $status];
            }
        }
        
        $healthScore = $totalFactors > 0 ? round($healthScore / $totalFactors) : 0;
        $healthLabel = $healthScore >= 90 ? 'Excellent' : ($healthScore >= 75 ? 'Good' : ($healthScore >= 60 ? 'Fair' : 'Needs Attention'));
        $healthColor = $healthScore >= 90 ? 'emerald' : ($healthScore >= 75 ? 'blue' : ($healthScore >= 60 ? 'amber' : 'red'));

        // Overall reading counts
        $totalReadings = HealthMetric::where('elderly_id', $elderlyId)
            ->whereIn('type', array_keys(self::VITAL_TYPES))
            ->count();

        $readingsThisWeek = HealthMetric::where('elderly_id', $elderlyId)
            ->whereIn('type', array_keys(self::VITAL_TYPES))
            ->where('measured_at', '>=', Carbon::now()->subDays(7))
            ->count();

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
                    
                    $takenCount = MedicationLog::where('medication_id', $med->id)
                        ->whereDate('scheduled_time', $date)
                        ->where('is_taken', true)
                        ->count();
                    $taken += $takenCount;
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
        if ($metrics->count() < 3) return 'stable';
        
        $values = $metrics->take(7)->pluck('value')->map(fn($v) => floatval($v))->toArray();
        $firstHalf = array_slice($values, 0, (int)ceil(count($values)/2));
        $secondHalf = array_slice($values, (int)ceil(count($values)/2));
        
        $firstAvg = count($firstHalf) > 0 ? array_sum($firstHalf) / count($firstHalf) : 0;
        $secondAvg = count($secondHalf) > 0 ? array_sum($secondHalf) / count($secondHalf) : 0;
        
        $diff = $secondAvg - $firstAvg;
        $threshold = $firstAvg * 0.05;
        
        if ($diff > $threshold) return 'increasing';
        if ($diff < -$threshold) return 'decreasing';
        return 'stable';
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
        
        // Get vitals analytics data (7 days only for PDF)
        $startDate = Carbon::now()->subDays(7);

        $analyticsData = [];
        $healthScore = 0;
        $healthFactors = [];
        $totalFactors = 0;

        foreach (self::VITAL_TYPES as $type => $config) {
            $data = [
                'config' => $config,
                'type' => $type,
            ];

            $metrics = HealthMetric::where('elderly_id', $elderlyId)
                ->where('type', $type)
                ->where('measured_at', '>=', $startDate)
                ->orderBy('measured_at', 'asc')
                ->get();

            $periodData = [
                'count' => $metrics->count(),
                'metrics' => $metrics,
            ];

            if ($type === 'blood_pressure') {
                $systolic = [];
                $diastolic = [];
                foreach ($metrics as $metric) {
                    if ($metric->value_text && preg_match('/^(\d+)\/(\d+)$/', $metric->value_text, $matches)) {
                        $systolic[] = intval($matches[1]);
                        $diastolic[] = intval($matches[2]);
                    }
                }
                if (!empty($systolic)) {
                    $periodData['systolic_avg'] = round(array_sum($systolic) / count($systolic), 1);
                    $periodData['systolic_min'] = min($systolic);
                    $periodData['systolic_max'] = max($systolic);
                    $periodData['diastolic_avg'] = round(array_sum($diastolic) / count($diastolic), 1);
                    $periodData['diastolic_min'] = min($diastolic);
                    $periodData['diastolic_max'] = max($diastolic);
                }
            } else {
                if ($metrics->isNotEmpty()) {
                    $values = $metrics->pluck('value')->map(fn($v) => floatval($v));
                    $periodData['avg'] = round($values->avg(), 1);
                    $periodData['min'] = $values->min();
                    $periodData['max'] = $values->max();
                }
            }

            $data['7days'] = $periodData;
            $analyticsData[$type] = $data;
            
            // Calculate health score contribution
            if (($data['7days']['count'] ?? 0) > 0) {
                $totalFactors++;
                $score = 0;
                $status = 'unknown';
                
                if ($type === 'blood_pressure') {
                    $sys = $data['7days']['systolic_avg'] ?? 120;
                    $dia = $data['7days']['diastolic_avg'] ?? 80;
                    if ($sys < 120 && $dia < 80) { $score = 100; $status = 'Optimal'; }
                    elseif ($sys < 130 && $dia < 85) { $score = 85; $status = 'Normal'; }
                    elseif ($sys < 140 && $dia < 90) { $score = 70; $status = 'Elevated'; }
                    else { $score = 50; $status = 'High'; }
                } elseif ($type === 'heart_rate') {
                    $hr = $data['7days']['avg'] ?? 72;
                    if ($hr >= 60 && $hr <= 100) { $score = 100; $status = 'Optimal'; }
                    elseif ($hr >= 50 && $hr <= 110) { $score = 80; $status = 'Normal'; }
                    else { $score = 60; $status = 'Attention'; }
                } elseif ($type === 'temperature') {
                    $temp = $data['7days']['avg'] ?? 36.5;
                    if ($temp >= 36.1 && $temp <= 37.2) { $score = 100; $status = 'Normal'; }
                    elseif ($temp >= 35.5 && $temp <= 37.8) { $score = 75; $status = 'Mild'; }
                    else { $score = 50; $status = 'Attention'; }
                } elseif ($type === 'sugar_level') {
                    $sugar = $data['7days']['avg'] ?? 100;
                    if ($sugar >= 70 && $sugar <= 100) { $score = 100; $status = 'Optimal'; }
                    elseif ($sugar >= 60 && $sugar <= 125) { $score = 80; $status = 'Normal'; }
                    else { $score = 60; $status = 'Attention'; }
                }
                
                $healthScore += $score;
                $healthFactors[$type] = ['score' => $score, 'status' => $status];
            }
        }
        
        $healthScore = $totalFactors > 0 ? round($healthScore / $totalFactors) : 0;
        $healthLabel = $healthScore >= 90 ? 'Excellent' : ($healthScore >= 75 ? 'Good' : ($healthScore >= 60 ? 'Fair' : 'Needs Attention'));

        // Overall reading counts
        $totalReadings = HealthMetric::where('elderly_id', $elderlyId)
            ->whereIn('type', array_keys(self::VITAL_TYPES))
            ->count();

        $readingsThisWeek = HealthMetric::where('elderly_id', $elderlyId)
            ->whereIn('type', array_keys(self::VITAL_TYPES))
            ->where('measured_at', '>=', Carbon::now()->subDays(7))
            ->count();

        // Medication & Task Summary
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

