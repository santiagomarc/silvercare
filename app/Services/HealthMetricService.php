<?php

namespace App\Services;

use App\Models\HealthMetric;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HealthMetricService
{
    /**
     * Add a health metric (heart rate, blood pressure, mood, etc.)
     */
    public function addHealthMetric(array $data): HealthMetric
    {
        return HealthMetric::create([
            'elderly_id' => $data['elderly_id'],
            'type' => $data['type'], // heart_rate, blood_pressure, sugar_level, temperature, mood, steps, calories, sleep, weight
            'value' => $data['value'] ?? null, // Numeric value
            'value_text' => $data['value_text'] ?? null, // For mood: "happy", "sad", etc.
            'unit' => $data['unit'] ?? null, // bpm, mmHg, mg/dL, °C, kg, etc.
            'notes' => $data['notes'] ?? null,
            'measured_at' => $data['measured_at'] ?? Carbon::now(),
            'source' => $data['source'] ?? 'manual', // manual, google_fit, device
        ]);
    }

    /**
     * Get heart rate records
     */
    public function getHeartRateRecords(int $elderlyProfileId, Carbon $startDate, Carbon $endDate): Collection
    {
        return HealthMetric::heartRate()
            ->where('elderly_id', $elderlyProfileId)
            ->whereBetween('measured_at', [$startDate, $endDate])
            ->orderBy('measured_at', 'desc')
            ->get();
    }

    /**
     * Get blood pressure records
     */
    public function getBloodPressureRecords(int $elderlyProfileId, Carbon $startDate, Carbon $endDate): Collection
    {
        return HealthMetric::bloodPressure()
            ->where('elderly_id', $elderlyProfileId)
            ->whereBetween('measured_at', [$startDate, $endDate])
            ->orderBy('measured_at', 'desc')
            ->get();
    }

    /**
     * Get blood sugar records
     */
    public function getBloodSugarRecords(int $elderlyProfileId, Carbon $startDate, Carbon $endDate): Collection
    {
        return HealthMetric::where('type', 'sugar_level')
            ->where('elderly_id', $elderlyProfileId)
            ->whereBetween('measured_at', [$startDate, $endDate])
            ->orderBy('measured_at', 'desc')
            ->get();
    }

    /**
     * Get temperature records
     */
    public function getTemperatureRecords(int $elderlyProfileId, Carbon $startDate, Carbon $endDate): Collection
    {
        return HealthMetric::where('type', 'temperature')
            ->where('elderly_id', $elderlyProfileId)
            ->whereBetween('measured_at', [$startDate, $endDate])
            ->orderBy('measured_at', 'desc')
            ->get();
    }

    /**
     * Get mood records
     */
    public function getMoodRecords(int $elderlyProfileId, Carbon $startDate, Carbon $endDate): Collection
    {
        return HealthMetric::mood()
            ->where('elderly_id', $elderlyProfileId)
            ->whereBetween('measured_at', [$startDate, $endDate])
            ->orderBy('measured_at', 'desc')
            ->get();
    }

    /**
     * Get steps records (from Google Fit or manual)
     */
    public function getStepsRecords(int $elderlyProfileId, Carbon $startDate, Carbon $endDate): Collection
    {
        return HealthMetric::steps()
            ->where('elderly_id', $elderlyProfileId)
            ->whereBetween('measured_at', [$startDate, $endDate])
            ->orderBy('measured_at', 'desc')
            ->get();
    }

    /**
     * Get calories records (from Google Fit)
     */
    public function getCaloriesRecords(int $elderlyProfileId, Carbon $startDate, Carbon $endDate): Collection
    {
        return HealthMetric::calories()
            ->where('elderly_id', $elderlyProfileId)
            ->whereBetween('measured_at', [$startDate, $endDate])
            ->orderBy('measured_at', 'desc')
            ->get();
    }

    /**
     * Get all health metrics for dashboard
     */
    public function getDashboardMetrics(int $elderlyProfileId): array
    {
        $today = Carbon::today();
        $lastWeek = Carbon::today()->subDays(7);

        return [
            'latest_heart_rate' => $this->getLatestMetric($elderlyProfileId, 'heart_rate'),
            'latest_blood_pressure' => $this->getLatestMetric($elderlyProfileId, 'blood_pressure'),
            'latest_sugar_level' => $this->getLatestMetric($elderlyProfileId, 'sugar_level'),
            'latest_temperature' => $this->getLatestMetric($elderlyProfileId, 'temperature'),
            'latest_weight' => $this->getLatestMetric($elderlyProfileId, 'weight'),
            'latest_mood' => $this->getLatestMetric($elderlyProfileId, 'mood'),
            'today_steps' => $this->getTodaySteps($elderlyProfileId),
            'today_calories' => $this->getTodayCalories($elderlyProfileId),
            'week_summary' => $this->getWeeklySummary($elderlyProfileId, $lastWeek, $today),
        ];
    }

    /**
     * Get latest metric of specific type
     */
    private function getLatestMetric(int $elderlyProfileId, string $type): ?HealthMetric
    {
        return HealthMetric::where('elderly_id', $elderlyProfileId)
            ->where('type', $type)
            ->latest('measured_at')
            ->first();
    }

    /**
     * Get today's total steps
     */
    private function getTodaySteps(int $elderlyProfileId): int
    {
        return HealthMetric::steps()
            ->where('elderly_id', $elderlyProfileId)
            ->whereDate('measured_at', Carbon::today())
            ->sum('value') ?? 0;
    }

    /**
     * Get today's total calories
     */
    private function getTodayCalories(int $elderlyProfileId): int
    {
        return HealthMetric::calories()
            ->where('elderly_id', $elderlyProfileId)
            ->whereDate('measured_at', Carbon::today())
            ->sum('value') ?? 0;
    }

    /**
     * Get weekly summary of all metrics
     */
    private function getWeeklySummary(int $elderlyProfileId, Carbon $startDate, Carbon $endDate): array
    {
        $metrics = HealthMetric::where('elderly_id', $elderlyProfileId)
            ->whereBetween('measured_at', [$startDate, $endDate])
            ->get()
            ->groupBy('type');

        $summary = [];
        foreach ($metrics as $type => $records) {
            $summary[$type] = [
                'count' => $records->count(),
                'avg' => round($records->avg('value'), 2),
                'min' => $records->min('value'),
                'max' => $records->max('value'),
            ];
        }

        return $summary;
    }

    /**
     * Delete health metric
     */
    public function deleteHealthMetric(int $metricId): bool
    {
        $metric = HealthMetric::findOrFail($metricId);
        return $metric->delete();
    }
}

