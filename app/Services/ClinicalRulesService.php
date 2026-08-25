<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\DoseInstance;
use App\Models\HealthMetric;
use App\Models\PatientAlertThreshold;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ClinicalRulesService
{
    public function __construct(
        protected AlertDeliveryService $deliveryService,
    ) {
    }

    /**
     * Evaluate a recorded vital reading against clinical thresholds.
     */
    public function evaluateVitalReading(HealthMetric $metric): ?Alert
    {
        $elderly = $metric->elderly;
        if (!$elderly) {
            return null;
        }

        $patientName = $elderly->user?->name ?? 'Patient';

        // Load custom or default thresholds
        $thresholds = $this->resolveThresholds($metric->elderly_id, $metric->type);
        if (!$thresholds) {
            return null;
        }

        $evaluation = match ($metric->type) {
            'blood_pressure' => $this->evaluateBloodPressure($metric, $thresholds, $patientName),
            'sugar_level' => $this->evaluateNumericVital($metric, $thresholds, $patientName, 'Blood Sugar', 'mg/dL'),
            'temperature' => $this->evaluateNumericVital($metric, $thresholds, $patientName, 'Temperature', '°C'),
            'heart_rate' => $this->evaluateNumericVital($metric, $thresholds, $patientName, 'Heart Rate', 'BPM'),
            default => null,
        };

        if (!$evaluation) {
            return null;
        }

        $escalateMinutes = config("alerts.escalation_minutes.{$evaluation['severity']}", 15);

        $alert = Alert::create([
            'elderly_id' => $metric->elderly_id,
            'severity' => $evaluation['severity'],
            'source_type' => 'vital_threshold',
            'source_id' => $metric->id,
            'title' => $evaluation['title'],
            'message' => $evaluation['message'],
            'metadata' => [
                'metric_id' => $metric->id,
                'metric_type' => $metric->type,
                'value' => $metric->value,
                'value_text' => $metric->value_text,
                'unit' => $metric->unit,
                'measured_at' => $metric->measured_at?->toISOString(),
            ],
            'state' => 'open',
            'escalate_at' => Carbon::now()->addMinutes($escalateMinutes),
        ]);

        $this->deliveryService->deliver($alert);

        return $alert;
    }

    /**
     * Evaluate an SOS trigger event.
     */
    public function evaluateSos(UserProfile $elderly, ?string $notes = null): Alert
    {
        $patientName = $elderly->user?->name ?? 'Patient';
        $escalateMinutes = config('alerts.escalation_minutes.emergency', 10);

        $alert = Alert::create([
            'elderly_id' => $elderly->id,
            'severity' => 'emergency',
            'source_type' => 'sos',
            'source_id' => null,
            'title' => "🚨 SOS Emergency Alert from {$patientName}",
            'message' => "{$patientName} pressed the Emergency SOS button. Immediate caregiver attention is required.",
            'metadata' => [
                'triggered_at' => Carbon::now()->toISOString(),
                'notes' => $notes,
            ],
            'state' => 'open',
            'escalate_at' => Carbon::now()->addMinutes($escalateMinutes),
        ]);

        $this->deliveryService->deliver($alert);

        return $alert;
    }

    /**
     * Evaluate AI-detected emergency intent from conversation.
     */
    public function evaluateAiEmergencyIntent(UserProfile $elderly, string $keyword, string $message): Alert
    {
        $patientName = $elderly->user?->name ?? 'Patient';
        $escalateMinutes = config('alerts.escalation_minutes.emergency', 10);

        $alert = Alert::create([
            'elderly_id' => $elderly->id,
            'severity' => 'emergency',
            'source_type' => 'ai_emergency',
            'source_id' => null,
            'title' => "🚨 Emergency Detected: {$keyword}",
            'message' => "{$patientName} mentioned urgent distress during conversation: \"{$message}\" (detected: {$keyword}).",
            'metadata' => [
                'keyword' => $keyword,
                'user_message' => $message,
                'timestamp' => Carbon::now()->toISOString(),
            ],
            'state' => 'open',
            'escalate_at' => Carbon::now()->addMinutes($escalateMinutes),
        ]);

        $this->deliveryService->deliver($alert);

        return $alert;
    }

    /**
     * Resolve thresholds with custom override fallback to config.
     */
    protected function resolveThresholds(int $elderlyId, string $metricType): ?array
    {
        $custom = PatientAlertThreshold::where('elderly_id', $elderlyId)
            ->where('metric_type', $metricType)
            ->first();

        if ($custom && is_array($custom->thresholds)) {
            return $custom->thresholds;
        }

        return config("alerts.thresholds.{$metricType}");
    }

    protected function evaluateBloodPressure(HealthMetric $metric, array $thresholds, string $patientName): ?array
    {
        $text = $metric->value_text;
        if (!$text || !str_contains($text, '/')) {
            return null;
        }

        [$systolicStr, $diastolicStr] = explode('/', $text, 2);
        $systolic = (int) trim($systolicStr);
        $diastolic = (int) trim($diastolicStr);

        $critSysHigh = $thresholds['critical_systolic_high'] ?? 180;
        $critSysLow  = $thresholds['critical_systolic_low'] ?? 85;
        $critDiaHigh = $thresholds['critical_diastolic_high'] ?? 120;
        $critDiaLow  = $thresholds['critical_diastolic_low'] ?? 50;

        $warnSysHigh = $thresholds['warning_systolic_high'] ?? 140;
        $warnDiaHigh = $thresholds['warning_diastolic_high'] ?? 90;

        if ($systolic >= $critSysHigh || $systolic <= $critSysLow || $diastolic >= $critDiaHigh || $diastolic <= $critDiaLow) {
            return [
                'severity' => 'critical',
                'title' => "🚨 Critical Blood Pressure ({$text} mmHg)",
                'message' => "A critical blood pressure reading of {$text} mmHg was recorded for {$patientName}. Immediate clinical review is recommended.",
            ];
        }

        if ($systolic >= $warnSysHigh || $diastolic >= $warnDiaHigh) {
            return [
                'severity' => 'warning',
                'title' => "⚠️ Elevated Blood Pressure ({$text} mmHg)",
                'message' => "An elevated blood pressure reading of {$text} mmHg was recorded for {$patientName}.",
            ];
        }

        return null;
    }

    protected function evaluateNumericVital(
        HealthMetric $metric,
        array $thresholds,
        string $patientName,
        string $label,
        string $unit
    ): ?array {
        $val = (float) $metric->value;
        if ($val <= 0) {
            return null;
        }

        $critHigh = $thresholds['critical_high'] ?? null;
        $critLow  = $thresholds['critical_low'] ?? null;
        $warnHigh = $thresholds['warning_high'] ?? null;
        $warnLow  = $thresholds['warning_low'] ?? null;

        if (($critHigh !== null && $val >= $critHigh) || ($critLow !== null && $val <= $critLow)) {
            return [
                'severity' => 'critical',
                'title' => "🚨 Critical {$label} ({$val} {$unit})",
                'message' => "A critical {$label} reading of {$val} {$unit} was recorded for {$patientName}.",
            ];
        }

        if (($warnHigh !== null && $val >= $warnHigh) || ($warnLow !== null && $val <= $warnLow)) {
            return [
                'severity' => 'warning',
                'title' => "⚠️ Abnormal {$label} ({$val} {$unit})",
                'message' => "An abnormal {$label} reading of {$val} {$unit} was recorded for {$patientName}.",
            ];
        }

        return null;
    }
}
