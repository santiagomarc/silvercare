<?php

namespace App\Services;

use App\Models\HealthMetric;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HealthAnalyticsService
{
    /**
     * Fetch analytics data for an elderly user across multiple time periods.
     *
     * @param int   $elderlyId
     * @param array $periods  e.g. ['7days' => Carbon, '30days' => Carbon]
     * @param array $types    vital type keys (defaults to config('vitals.scorable_types'))
     * @return array  keyed by type → ['config'=>…, 'type'=>…, '<periodKey>'=>…]
     */
    public function getAnalyticsData(int $elderlyId, array $periods, ?array $types = null): array
    {
        $types = $types ?? config('vitals.scorable_types');
        $allVitals = config('vitals');

        $periodBoundaries = collect($periods)->mapWithKeys(
            fn ($startDate, $periodKey) => [$periodKey => $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate)]
        );

        $earliestStart = $periodBoundaries
            ->sortBy(fn (Carbon $date) => $date->getTimestamp())
            ->first();

        $allMetrics = $earliestStart
            ? HealthMetric::where('elderly_id', $elderlyId)
                ->whereIn('type', $types)
                ->where('measured_at', '>=', $earliestStart)
                ->orderBy('measured_at', 'asc')
                ->get()
                ->groupBy('type')
            : collect();

        $analyticsData = [];

        foreach ($types as $type) {
            $conf = $allVitals[$type] ?? [];
            $typeMetrics = $allMetrics->get($type, collect());

            $data = [
                'config' => $conf,
                'type'   => $type,
            ];

            foreach ($periodBoundaries as $periodKey => $startDate) {
                $metrics = $typeMetrics
                    ->filter(fn ($metric) => $metric->measured_at->gte($startDate))
                    ->values();

                $periodData = [
                    'count'   => $metrics->count(),
                    'metrics' => $metrics,
                ];

                if ($type === 'blood_pressure') {
                    $periodData = array_merge($periodData, $this->parseBloodPressureMetrics($metrics));
                } elseif ($metrics->isNotEmpty()) {
                    $values = $metrics->pluck('value')->map(fn ($v) => floatval($v));
                    $periodData['avg']   = round($values->avg(), 1);
                    $periodData['min']   = $values->min();
                    $periodData['max']   = $values->max();
                    $periodData['trend'] = $this->calculateTrend($metrics);
                }

                $data[$periodKey] = $periodData;
            }

            $analyticsData[$type] = $data;
        }

        return $analyticsData;
    }

    /**
     * Compute a composite health score from 7-day analytics data.
     *
     * @param array $analyticsData  output of getAnalyticsData (must contain a '7days' period)
     * @return array ['score'=>int, 'label'=>string, 'color'=>string, 'factors'=>array, 'totalFactors'=>int]
     */
    public function calculateHealthScore(array $analyticsData): array
    {
        $healthScore   = 0;
        $healthFactors = [];
        $totalFactors  = 0;

        foreach ($analyticsData as $type => $data) {
            $sevenDay = $data['7days'] ?? null;
            if (!$sevenDay || ($sevenDay['count'] ?? 0) === 0) {
                continue;
            }

            $totalFactors++;
            $score  = 0;
            $status = 'unknown';

            switch ($type) {
                case 'blood_pressure':
                    $sys = $sevenDay['systolic_avg'] ?? 120;
                    $dia = $sevenDay['diastolic_avg'] ?? 80;
                    if ($sys < 120 && $dia < 80)      { $score = 100; $status = 'Optimal'; }
                    elseif ($sys < 130 && $dia < 85)   { $score = 85;  $status = 'Normal'; }
                    elseif ($sys < 140 && $dia < 90)   { $score = 70;  $status = 'Elevated'; }
                    else                               { $score = 50;  $status = 'High'; }
                    break;

                case 'heart_rate':
                    $hr = $sevenDay['avg'] ?? 72;
                    if ($hr >= 60 && $hr <= 100)       { $score = 100; $status = 'Optimal'; }
                    elseif ($hr >= 50 && $hr <= 110)   { $score = 80;  $status = 'Normal'; }
                    else                               { $score = 60;  $status = 'Attention'; }
                    break;

                case 'temperature':
                    $temp = $sevenDay['avg'] ?? 36.5;
                    if ($temp >= 36.1 && $temp <= 37.2)  { $score = 100; $status = 'Normal'; }
                    elseif ($temp >= 35.5 && $temp <= 37.8) { $score = 75; $status = 'Mild'; }
                    else                                 { $score = 50;  $status = 'Attention'; }
                    break;

                case 'sugar_level':
                    $sugar = $sevenDay['avg'] ?? 100;
                    if ($sugar >= 70 && $sugar <= 100)   { $score = 100; $status = 'Optimal'; }
                    elseif ($sugar >= 60 && $sugar <= 125) { $score = 80; $status = 'Normal'; }
                    else                                 { $score = 60;  $status = 'Attention'; }
                    break;
            }

            $healthScore += $score;
            $healthFactors[$type] = ['score' => $score, 'status' => $status];
        }

        $finalScore = $totalFactors > 0 ? round($healthScore / $totalFactors) : 0;
        $label = match (true) {
            $finalScore >= 90 => 'Excellent',
            $finalScore >= 75 => 'Good',
            $finalScore >= 60 => 'Fair',
            default           => 'Needs Attention',
        };
        $color = match (true) {
            $finalScore >= 90 => 'emerald',
            $finalScore >= 75 => 'blue',
            $finalScore >= 60 => 'amber',
            default           => 'red',
        };

        return [
            'score'        => $finalScore,
            'label'        => $label,
            'color'        => $color,
            'factors'      => $healthFactors,
            'totalFactors' => $totalFactors,
        ];
    }

    /**
     * Parse blood-pressure text values ("120/80") into aggregated stats.
     */
    public function parseBloodPressureMetrics(Collection $metrics): array
    {
        $systolic  = [];
        $diastolic = [];

        foreach ($metrics as $metric) {
            if ($metric->value_text && preg_match('/^(\d+)\/(\d+)$/', $metric->value_text, $m)) {
                $systolic[]  = intval($m[1]);
                $diastolic[] = intval($m[2]);
            }
        }

        if (empty($systolic)) {
            return [];
        }

        return [
            'systolic_avg'  => round(array_sum($systolic)  / count($systolic), 1),
            'systolic_min'  => min($systolic),
            'systolic_max'  => max($systolic),
            'diastolic_avg' => round(array_sum($diastolic) / count($diastolic), 1),
            'diastolic_min' => min($diastolic),
            'diastolic_max' => max($diastolic),
        ];
    }

    /**
     * Determine whether a metric series is increasing, decreasing, or stable.
     */
    public function calculateTrend(Collection $metrics): string
    {
        if ($metrics->count() < 2) {
            return 'stable';
        }

        $values     = $metrics->pluck('value')->map(fn ($v) => floatval($v))->values();
        $firstHalf  = $values->slice(0, ceil($values->count() / 2))->avg();
        $secondHalf = $values->slice(ceil($values->count() / 2))->avg();

        $diff          = $secondHalf - $firstHalf;
        $percentChange = $firstHalf > 0 ? ($diff / $firstHalf) * 100 : 0;

        if (abs($percentChange) < 3) {
            return 'stable';
        }

        return $percentChange > 0 ? 'increasing' : 'decreasing';
    }

    /**
     * Get total / this-week reading counts for an elderly user.
     */
    public function getReadingCounts(int $elderlyId, ?array $types = null): array
    {
        $types = $types ?? config('vitals.scorable_types');

        $totalReadings = HealthMetric::where('elderly_id', $elderlyId)
            ->whereIn('type', $types)
            ->count();

        $readingsThisWeek = HealthMetric::where('elderly_id', $elderlyId)
            ->whereIn('type', $types)
            ->where('measured_at', '>=', Carbon::now()->subDays(7))
            ->count();

        return [
            'total'    => $totalReadings,
            'thisWeek' => $readingsThisWeek,
        ];
    }
}
