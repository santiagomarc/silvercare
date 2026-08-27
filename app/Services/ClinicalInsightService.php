<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\CareCheckin;
use App\Models\DoseInstance;
use App\Models\HealthMetric;
use App\Models\UserProfile;
use Carbon\Carbon;

class ClinicalInsightService
{
    /**
     * Generate a comprehensive executive risk briefing for a patient.
     */
    public function getDailyBriefing(UserProfile $elderly): array
    {
        $tz = $elderly->timezone ?? config('app.timezone', 'Asia/Manila');
        $now = Carbon::now($tz);
        $sevenDaysAgo = Carbon::now($tz)->subDays(7);

        $riskAssessment = $this->calculateRiskScore($elderly);
        $freshness = $this->getDataFreshness($elderly);
        $medicationAdherence = $this->getMedicationAdherence7Days($elderly);
        $openAlerts = Alert::where('elderly_id', $elderly->id)
            ->whereIn('state', ['open', 'acknowledged'])
            ->orderByRaw("CASE severity WHEN 'emergency' THEN 1 WHEN 'critical' THEN 2 WHEN 'warning' THEN 3 ELSE 4 END")
            ->get();

        $todayCheckin = $elderly->todayCheckin();

        // Clinical highlights / executive summary bullets
        $highlights = $this->generateClinicalHighlights($elderly, $riskAssessment, $freshness, $medicationAdherence, $openAlerts, $todayCheckin);

        return [
            'patient_id' => $elderly->id,
            'patient_name' => $elderly->user?->name ?? 'Patient',
            'generated_at' => $now->toISOString(),
            'risk' => $riskAssessment,
            'freshness' => $freshness,
            'medication_adherence' => $medicationAdherence,
            'open_alerts_count' => $openAlerts->count(),
            'open_alerts' => $openAlerts,
            'today_checkin' => $todayCheckin,
            'highlights' => $highlights,
        ];
    }

    /**
     * Calculate composite risk score (0-100) and severity band.
     */
    public function calculateRiskScore(UserProfile $elderly): array
    {
        $score = 0;
        $factors = [];

        $sevenDaysAgo = Carbon::now()->subDays(7);

        // 1. Missed medication doses in past 7 days
        $missedDosesCount = DoseInstance::where('elderly_id', $elderly->id)
            ->where('scheduled_at_utc', '>=', $sevenDaysAgo)
            ->where('state', 'missed')
            ->count();

        if ($missedDosesCount > 0) {
            $dosePoints = min(40, $missedDosesCount * 15);
            $score += $dosePoints;
            $factors[] = "{$missedDosesCount} missed medication dose(s) in last 7 days (+{$dosePoints} pts)";
        }

        // 2. Critical & Emergency Alerts in past 7 days
        $criticalAlerts = Alert::where('elderly_id', $elderly->id)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->whereIn('severity', ['critical', 'emergency'])
            ->count();

        if ($criticalAlerts > 0) {
            $alertPoints = min(35, $criticalAlerts * 20);
            $score += $alertPoints;
            $factors[] = "{$criticalAlerts} critical/emergency clinical alert(s) in last 7 days (+{$alertPoints} pts)";
        }

        // 3. Warning Alerts in past 7 days
        $warningAlerts = Alert::where('elderly_id', $elderly->id)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->where('severity', 'warning')
            ->count();

        if ($warningAlerts > 0) {
            $warnPoints = min(20, $warningAlerts * 8);
            $score += $warnPoints;
            $factors[] = "{$warningAlerts} warning alert(s) in last 7 days (+{$warnPoints} pts)";
        }

        // 4. Missed or Need-Help Check-ins in past 7 days
        $missedCheckins = CareCheckin::where('elderly_id', $elderly->id)
            ->where('checkin_date', '>=', Carbon::today()->subDays(7)->toDateString())
            ->whereIn('status', ['missed', 'need_help'])
            ->count();

        if ($missedCheckins > 0) {
            $checkinPoints = min(20, $missedCheckins * 10);
            $score += $checkinPoints;
            $factors[] = "{$missedCheckins} missed or help-request check-in(s) in last 7 days (+{$checkinPoints} pts)";
        }

        // Clamp 0 - 100
        $clampedScore = (int) max(0, min(100, $score));

        $level = match (true) {
            $clampedScore >= 66 => 'high',
            $clampedScore >= 31 => 'moderate',
            default => 'low',
        };

        $color = match ($level) {
            'high' => 'red',
            'moderate' => 'amber',
            default => 'emerald',
        };

        return [
            'score' => $clampedScore,
            'level' => $level, // low, moderate, high
            'color' => $color,
            'factors' => $factors,
        ];
    }

    /**
     * Evaluate data freshness across key vital metrics.
     */
    public function getDataFreshness(UserProfile $elderly): array
    {
        $types = ['blood_pressure', 'sugar_level', 'heart_rate', 'temperature'];
        $freshness = [];

        foreach ($types as $type) {
            $latest = HealthMetric::where('elderly_id', $elderly->id)
                ->where('type', $type)
                ->latest('measured_at')
                ->first();

            if (!$latest) {
                $freshness[$type] = [
                    'last_recorded' => null,
                    'hours_ago' => null,
                    'is_stale' => true,
                    'status_label' => 'Never recorded',
                    'value_text' => null,
                ];
                continue;
            }

            $hoursAgo = (int) Carbon::now()->diffInHours($latest->measured_at);
            $isStale = $hoursAgo > 48; // stale if > 48 hours without measurement

            $freshness[$type] = [
                'last_recorded' => $latest->measured_at->toISOString(),
                'hours_ago' => $hoursAgo,
                'is_stale' => $isStale,
                'status_label' => $latest->measured_at->diffForHumans(),
                'value_text' => $latest->value_text ?? ($latest->value . ' ' . $latest->unit),
            ];
        }

        return $freshness;
    }

    /**
     * Calculate medication adherence percentage for past 7 days.
     */
    public function getMedicationAdherence7Days(UserProfile $elderly): array
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $totalScheduled = DoseInstance::where('elderly_id', $elderly->id)
            ->where('scheduled_at_utc', '>=', $sevenDaysAgo)
            ->where('scheduled_at_utc', '<=', Carbon::now())
            ->count();

        if ($totalScheduled === 0) {
            return [
                'rate' => 100,
                'taken_count' => 0,
                'scheduled_count' => 0,
                'missed_count' => 0,
            ];
        }

        $takenCount = DoseInstance::where('elderly_id', $elderly->id)
            ->where('scheduled_at_utc', '>=', $sevenDaysAgo)
            ->whereIn('state', ['taken', 'taken_late'])
            ->count();

        $missedCount = DoseInstance::where('elderly_id', $elderly->id)
            ->where('scheduled_at_utc', '>=', $sevenDaysAgo)
            ->where('state', 'missed')
            ->count();

        $rate = (int) round(($takenCount / $totalScheduled) * 100);

        return [
            'rate' => $rate,
            'taken_count' => $takenCount,
            'scheduled_count' => $totalScheduled,
            'missed_count' => $missedCount,
        ];
    }

    protected function generateClinicalHighlights(
        UserProfile $elderly,
        array $risk,
        array $freshness,
        array $adherence,
        $openAlerts,
        ?CareCheckin $todayCheckin
    ): array {
        $highlights = [];

        // Adherence
        if ($adherence['rate'] < 80) {
            $highlights[] = "Medication adherence is at {$adherence['rate']}% (below 80% target) with {$adherence['missed_count']} missed dose(s).";
        } else {
            $highlights[] = "Medication adherence is strong at {$adherence['rate']}% over the last 7 days.";
        }

        // Open alerts
        if ($openAlerts->count() > 0) {
            $highlights[] = "There are {$openAlerts->count()} active clinical alert(s) requiring caregiver attention.";
        } else {
            $highlights[] = "All past clinical alerts have been acknowledged and resolved.";
        }

        // Today check-in
        if (!$todayCheckin) {
            $highlights[] = "Daily check-in has not been recorded yet today.";
        } elseif ($todayCheckin->status === 'need_help') {
            $highlights[] = "Patient requested help during today's check-in: \"" . ($todayCheckin->notes ?? 'Assistance needed') . "\".";
        } else {
            $highlights[] = "Patient checked in OK today (" . ($todayCheckin->checked_in_at?->format('g:i A') ?? 'Confirmed') . ").";
        }

        // Stale vitals
        $staleTypes = [];
        foreach ($freshness as $type => $data) {
            if ($data['is_stale']) {
                $staleTypes[] = str_replace('_', ' ', $type);
            }
        }
        if (!empty($staleTypes)) {
            $highlights[] = "Missing recent readings for: " . implode(', ', $staleTypes) . " (>48h overdue).";
        }

        return $highlights;
    }
}
