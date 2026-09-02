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
     * Evaluate a dose that has just been marked missed.
     *
     * One missed dose is noise — a senior running late, a nap, a slow morning.
     * A run of them is the signal a caregiver needs. This counts the unbroken
     * run of misses for the same medication and raises a caregiver alert once
     * the run reaches the configured threshold, escalating to critical as it
     * lengthens.
     *
     * While a run continues, the existing alert is updated in place rather than
     * a new one created per missed dose, so a patient who stops taking a
     * medication produces one escalating alert instead of a wall of them.
     *
     * Returns null when the run is still below the alerting threshold.
     */
    public function evaluateMissedDose(DoseInstance $dose): ?Alert
    {
        $elderly = $dose->elderly;
        $medication = $dose->medication;

        if (!$elderly || !$medication) {
            return null;
        }

        $warningAt = (int) config('alerts.missed_dose.consecutive_warning', 2);
        $criticalAt = (int) config('alerts.missed_dose.consecutive_critical', 3);

        $consecutive = $this->countConsecutiveMisses($dose);

        if ($consecutive < $warningAt) {
            return null;
        }

        $severity = $consecutive >= $criticalAt ? 'critical' : 'warning';
        $patientName = $elderly->user?->name ?? 'Patient';
        $icon = $severity === 'critical' ? '🚨' : '⚠️';

        $timezone = $dose->timezone ?: config('app.timezone', 'Asia/Manila');
        $localTime = $dose->scheduled_at_utc?->copy()->setTimezone($timezone);

        $title = "{$icon} {$consecutive} missed doses: {$medication->name}";
        $message = "{$patientName} has missed {$consecutive} consecutive doses of {$medication->name}"
            . ($medication->dosage ? " ({$medication->dosage} {$medication->dosage_unit})" : '')
            . '. The most recent was scheduled for '
            . ($localTime?->format('D j M, g:i A') ?? 'an earlier time') . '.';

        $metadata = [
            'medication_id' => $medication->id,
            'medication_name' => $medication->name,
            'dose_instance_id' => $dose->id,
            'consecutive_missed' => $consecutive,
            'scheduled_at_utc' => $dose->scheduled_at_utc?->toISOString(),
            'local_scheduled_time' => $localTime?->toISOString(),
        ];

        // An unresolved alert already tracking this run gets updated in place.
        $existing = Alert::where('elderly_id', $elderly->id)
            ->where('source_type', 'missed_dose')
            ->whereIn('state', ['open', 'acknowledged'])
            ->whereJsonContains('metadata->medication_id', $medication->id)
            ->orderByDesc('created_at')
            ->first();

        if ($existing) {
            $escalated = $severity === 'critical' && $existing->severity !== 'critical';

            $existing->update([
                'severity' => $severity,
                'source_id' => $dose->id,
                'title' => $title,
                'message' => $message,
                'metadata' => $metadata,
                // A worsening run re-opens an acknowledged alert: the caregiver
                // acknowledged two misses, not three.
                'state' => $escalated ? 'open' : $existing->state,
                'acknowledged_at' => $escalated ? null : $existing->acknowledged_at,
                'acknowledged_by' => $escalated ? null : $existing->acknowledged_by,
                'escalate_at' => Carbon::now()->addMinutes(
                    (int) config("alerts.escalation_minutes.{$severity}", 15)
                ),
            ]);

            if ($escalated) {
                $this->deliveryService->deliver($existing->fresh());
            }

            return $existing->fresh();
        }

        $alert = Alert::create([
            'elderly_id' => $elderly->id,
            'severity' => $severity,
            'source_type' => 'missed_dose',
            'source_id' => $dose->id,
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata,
            'state' => 'open',
            'escalate_at' => Carbon::now()->addMinutes(
                (int) config("alerts.escalation_minutes.{$severity}", 15)
            ),
        ]);

        $this->deliveryService->deliver($alert);

        return $alert;
    }

    /**
     * Length of the unbroken run of missed doses for this medication, ending at
     * (and including) the given dose.
     *
     * A taken dose breaks the run. So does a held or skipped one — those are
     * deliberate decisions, not lapses. A still-pending earlier dose also breaks
     * it: the scheduler has not judged it yet, and counting it would overstate
     * the run.
     */
    protected function countConsecutiveMisses(DoseInstance $dose): int
    {
        $lookback = (int) config('alerts.missed_dose.lookback_instances', 20);

        $recent = DoseInstance::where('elderly_id', $dose->elderly_id)
            ->where('medication_id', $dose->medication_id)
            ->where('scheduled_at_utc', '<=', $dose->scheduled_at_utc)
            ->orderByDesc('scheduled_at_utc')
            ->limit(max($lookback, 1))
            ->get();

        $run = 0;

        foreach ($recent as $instance) {
            if ($instance->state !== 'missed') {
                break;
            }

            $run++;
        }

        return $run;
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

        // M8: the low side. A systolic in the 90s is hypotensive for an older
        // adult and a fall risk, and previously produced nothing between
        // "normal" and the critical floor.
        $warnSysLow = $thresholds['warning_systolic_low'] ?? null;
        $warnDiaLow = $thresholds['warning_diastolic_low'] ?? null;

        if (($warnSysLow !== null && $systolic <= $warnSysLow)
            || ($warnDiaLow !== null && $diastolic <= $warnDiaLow)) {
            return [
                'severity' => 'warning',
                'title' => "⚠️ Low Blood Pressure ({$text} mmHg)",
                'message' => "A low blood pressure reading of {$text} mmHg was recorded for {$patientName}. "
                    . 'Low readings can cause dizziness and increase fall risk.',
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
