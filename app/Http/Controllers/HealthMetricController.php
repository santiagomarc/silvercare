<?php

namespace App\Http\Controllers;

use App\Models\HealthMetric;
use App\Services\HealthAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HealthMetricController extends Controller
{
    public function __construct(protected HealthAnalyticsService $analyticsService)
    {
    }

    /**
     * All vital type definitions live in config/vitals.php.
     * Helper to get them keyed by type (excluding meta keys).
     */
    private function vitalTypes(): array
    {
        return collect(config('vitals'))
            ->except(['scorable_types', 'required_daily'])
            ->toArray();
    }

    /**
     * Store a new health metric reading
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        if (!$elderlyId) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $vitalTypes = $this->vitalTypes();

        $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys($vitalTypes)),
            'value' => 'nullable|numeric',
            'value_text' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $type = $request->input('type');
        $config = $vitalTypes[$type];

        // Validate based on type
        if ($config['has_text_value'] ?? false) {
            // Blood pressure needs text value (e.g., "120/80")
            if (!$request->input('value_text')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Value is required for ' . $config['name']
                ], 422);
            }
            
            // Validate blood pressure format
            if ($type === 'blood_pressure') {
                $bp = $request->input('value_text');
                if (!preg_match('/^\d{2,3}\/\d{2,3}$/', $bp)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Blood pressure must be in format like 120/80'
                    ], 422);
                }
            }
        } else {
            // Numeric value required
            if (!$request->has('value') || $request->input('value') === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Value is required for ' . $config['name']
                ], 422);
            }

            $value = $request->input('value');
            $min = $config['min'] ?? 0;
            $max = $config['max'] ?? 999999;

            if ($value < $min || $value > $max) {
                return response()->json([
                    'success' => false,
                    'message' => "{$config['name']} must be between {$min} and {$max}"
                ], 422);
            }
        }

        // Create the health metric
        $metric = HealthMetric::create([
            'elderly_id' => $elderlyId,
            'type' => $type,
            'value' => $request->input('value'),
            'value_text' => $request->input('value_text'),
            'unit' => $config['unit'],
            'measured_at' => Carbon::now(),
            'source' => 'manual',
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => $config['name'] . ' recorded successfully!',
            'metric' => [
                'id' => $metric->id,
                'type' => $metric->type,
                'value' => $metric->value,
                'value_text' => $metric->value_text,
                'unit' => $metric->unit,
                'measured_at' => $metric->measured_at->toISOString(),
                'display_value' => $this->formatDisplayValue($metric),
            ],
        ]);
    }

    /**
     * Get today's vitals for the elderly user
     */
    public function today()
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        if (!$elderlyId) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $todayMetrics = HealthMetric::where('elderly_id', $elderlyId)
            ->whereDate('measured_at', Carbon::today())
            ->orderBy('measured_at', 'desc')
            ->get()
            ->groupBy('type');

        $vitals = [];
        foreach ($this->vitalTypes() as $type => $config) {
            $latestMetric = $todayMetrics->get($type)?->first();
            
            $vitals[$type] = [
                'name' => $config['name'],
                'icon' => $config['icon'],
                'unit' => $config['unit'],
                'color' => $config['color'],
                'recorded' => $latestMetric !== null,
                'value' => $latestMetric?->value,
                'value_text' => $latestMetric?->value_text,
                'display_value' => $latestMetric ? $this->formatDisplayValue($latestMetric) : null,
                'measured_at' => $latestMetric?->measured_at?->toISOString(),
                'measured_at_human' => $latestMetric?->measured_at?->format('g:i A'),
            ];
        }

        return response()->json([
            'success' => true,
            'vitals' => $vitals,
        ]);
    }

    /**
     * Get history of a specific vital type
     */
    public function history(Request $request, string $type)
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        if (!$elderlyId) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        if (!isset($this->vitalTypes()[$type])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid vital type'
            ], 422);
        }

        $days = $request->input('days', 7);
        
        $metrics = HealthMetric::where('elderly_id', $elderlyId)
            ->where('type', $type)
            ->where('measured_at', '>=', Carbon::now()->subDays($days))
            ->orderBy('measured_at', 'desc')
            ->get()
            ->map(function ($metric) {
                return [
                    'id' => $metric->id,
                    'value' => $metric->value,
                    'value_text' => $metric->value_text,
                    'display_value' => $this->formatDisplayValue($metric),
                    'unit' => $metric->unit,
                    'source' => $metric->source,
                    'notes' => $metric->notes,
                    'measured_at' => $metric->measured_at->toISOString(),
                    'measured_at_human' => $metric->measured_at->format('M j, g:i A'),
                ];
            });

        return response()->json([
            'success' => true,
            'type' => $type,
            'config' => $this->vitalTypes()[$type],
            'metrics' => $metrics,
        ]);
    }

    /**
     * Delete a health metric
     */
    public function destroy(HealthMetric $metric)
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        if ($metric->elderly_id !== $elderlyId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $metric->delete();

        return response()->json([
            'success' => true,
            'message' => 'Record deleted successfully',
        ]);
    }

    /**
     * Store mood value (auto-saved from dashboard slider)
     */
    public function storeMood(Request $request)
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        if (!$elderlyId) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $request->validate([
            'value' => 'required|integer|min:1|max:5',
        ]);

        // Update or create today's mood entry (only one mood per day)
        $metric = HealthMetric::updateOrCreate(
            [
                'elderly_id' => $elderlyId,
                'type' => 'mood',
                'measured_at' => Carbon::today(),
            ],
            [
                'value' => $request->input('value'),
                'unit' => '',
                'source' => 'manual',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Mood saved!',
            'value' => $metric->value,
        ]);
    }

    /**
     * Get today's mood (for loading dashboard)
     */
    public function getTodayMood()
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        if (!$elderlyId) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $mood = HealthMetric::where('elderly_id', $elderlyId)
            ->where('type', 'mood')
            ->whereDate('measured_at', Carbon::today())
            ->first();

        return response()->json([
            'success' => true,
            'value' => $mood?->value ?? 3, // Default to neutral (3)
        ]);
    }

    /**
     * Format display value based on metric type
     */
    private function formatDisplayValue(HealthMetric $metric): string
    {
        if ($metric->value_text) {
            return $metric->value_text;
        }

        if ($metric->type === 'temperature') {
            return number_format($metric->value, 1);
        }

        return (string) intval($metric->value);
    }

    /**
     * Blood Pressure Screen - with history and manual entry
     */
    public function bloodPressureScreen()
    {
        return $this->vitalScreen('blood_pressure');
    }

    /**
     * Sugar Level Screen - with history and manual entry
     */
    public function sugarLevelScreen()
    {
        return $this->vitalScreen('sugar_level');
    }

    /**
     * Temperature Screen - with history and manual entry
     */
    public function temperatureScreen()
    {
        return $this->vitalScreen('temperature');
    }

    /**
     * Heart Rate Screen - with history and manual entry
     */
    public function heartRateScreen()
    {
        return $this->vitalScreen('heart_rate');
    }

    /**
     * Analytics Dashboard - comprehensive view of all health vitals
     */
    public function analytics()
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        if (!$elderlyId) {
            return redirect()->route('elderly.dashboard')->with('error', 'Profile not found');
        }

        $periods = [
            '7days'  => Carbon::now()->subDays(7),
            '30days' => Carbon::now()->subDays(30),
            '90days' => Carbon::now()->subDays(90),
        ];

        $analyticsData = $this->analyticsService->getAnalyticsData($elderlyId, $periods);

        $readings = $this->analyticsService->getReadingCounts($elderlyId);
        $totalReadings    = $readings['total'];
        $readingsThisWeek = $readings['thisWeek'];

        // Get Steps data for analytics
        $stepsData = [
            'today' => null,
            'week' => null,
            'weeklyTotal' => 0,
            'weeklyAvg' => 0,
            'weeklyHistory' => [],
        ];

        // Today's steps
        $todaySteps = HealthMetric::where('elderly_id', $elderlyId)
            ->where('type', 'steps')
            ->whereDate('measured_at', Carbon::today())
            ->orderBy('measured_at', 'desc')
            ->first();

        if ($todaySteps) {
            $stepsData['today'] = [
                'value' => (int) $todaySteps->value,
                'goal' => 6000, // Default daily step goal for seniors
                'source' => $todaySteps->source,
                'synced_at' => $todaySteps->measured_at,
            ];
        }

        // Weekly steps (last 7 days)
        $weeklySteps = HealthMetric::where('elderly_id', $elderlyId)
            ->where('type', 'steps')
            ->where('measured_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('measured_at', 'asc')
            ->get();

        if ($weeklySteps->isNotEmpty()) {
            $stepsData['weeklyTotal'] = (int) $weeklySteps->sum('value');
            $stepsData['weeklyAvg'] = (int) round($weeklySteps->avg('value'));
            $stepsData['weeklyHistory'] = $weeklySteps->map(function($step) {
                return [
                    'date' => $step->measured_at->format('M d'),
                    'value' => (int) $step->value,
                ];
            })->toArray();
        }

        // Get BMI data (weight and height from profile)
        $userProfile = $user->profile;
        $bmiData = [
            'weight' => null,
            'height' => null,
            'bmi' => null,
            'category' => null,
            'color' => 'gray',
        ];

        if ($userProfile && $userProfile->weight && $userProfile->height) {
            $weight = floatval($userProfile->weight); // kg
            $height = floatval($userProfile->height) / 100; // convert cm to m

            if ($height > 0) {
                $bmi = round($weight / ($height * $height), 1);
                $bmiData['weight'] = $weight;
                $bmiData['height'] = $userProfile->height;
                $bmiData['bmi'] = $bmi;

                // Determine BMI category
                if ($bmi < 18.5) {
                    $bmiData['category'] = 'Underweight';
                    $bmiData['color'] = 'blue';
                } elseif ($bmi < 25) {
                    $bmiData['category'] = 'Normal';
                    $bmiData['color'] = 'green';
                } elseif ($bmi < 30) {
                    $bmiData['category'] = 'Overweight';
                    $bmiData['color'] = 'amber';
                } else {
                    $bmiData['category'] = 'Obese';
                    $bmiData['color'] = 'red';
                }
            }
        }

        return view('elderly.vitals.analytics', compact(
            'analyticsData',
            'totalReadings',
            'readingsThisWeek',
            'stepsData',
            'bmiData'
        ));
    }

    /**
     * Calculate trend (increasing, decreasing, stable)
     */
    private function calculateTrend($metrics)
    {
        return $this->analyticsService->calculateTrend($metrics);
    }

    /**
     * Generic vital screen with history
     */
    private function vitalScreen(string $type)
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;
        $config = $this->vitalTypes()[$type] ?? null;

        if (!$config) {
            abort(404, 'Vital type not found');
        }

        // Check Google Fit connection status
        $googleFitConnected = \App\Models\GoogleFitToken::where('user_id', $user->id)->exists();

        // Vitals that support Google Fit sync (all except sugar_level)
        $supportsGoogleFit = in_array($type, ['heart_rate', 'blood_pressure', 'temperature']);

        // Get history for last 30 days
        $metrics = collect();
        if ($elderlyId) {
            $metrics = HealthMetric::where('elderly_id', $elderlyId)
                ->where('type', $type)
                ->where('measured_at', '>=', Carbon::now()->subDays(30))
                ->orderBy('measured_at', 'desc')
                ->get();
        }

        // Calculate stats
        $stats = [
            'count' => $metrics->count(),
            'latest' => $metrics->first(),
        ];

        if ($metrics->isNotEmpty() && $type !== 'blood_pressure') {
            $stats['avg'] = round($metrics->avg('value'), 1);
            $stats['min'] = $metrics->min('value');
            $stats['max'] = $metrics->max('value');
        }

        return view('elderly.vitals.show', compact(
            'type',
            'config',
            'metrics',
            'stats',
            'googleFitConnected',
            'supportsGoogleFit'
        ));
    }

    /**
     * Export health analytics as PDF for elderly user
     */
    public function exportPdf()
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        if (!$elderlyId) {
            return redirect()->route('dashboard')->with('error', 'Profile not found');
        }

        $periods = ['7days' => Carbon::now()->subDays(7)];
        $analyticsData = $this->analyticsService->getAnalyticsData($elderlyId, $periods);
        $health = $this->analyticsService->calculateHealthScore($analyticsData);
        $readings = $this->analyticsService->getReadingCounts($elderlyId);

        $healthScore   = $health['score'];
        $healthLabel   = $health['label'];
        $healthFactors = $health['factors'];
        $totalReadings    = $readings['total'];
        $readingsThisWeek = $readings['thisWeek'];

        // Elderly PDF has no medication/task summary
        $medicationSummary = [
            'totalMedications' => 0,
            'adherenceRate' => null,
            'medications' => [],
            'lowStockCount' => 0,
        ];
        $taskSummary = [
            'total' => 0,
            'completionRate' => null,
        ];

        $elderly = $user->profile;
        $elderlyUser = $user;

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

        $filename = 'SilverCare_My_Health_Report_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}

