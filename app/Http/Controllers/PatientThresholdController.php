<?php

namespace App\Http\Controllers;

use App\Models\PatientAlertThreshold;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientThresholdController extends Controller
{
    /**
     * Get patient's custom and default alert thresholds.
     */
    public function index(Request $request, UserProfile $patient): JsonResponse
    {
        $caregiver = Auth::user()->profile;

        if (!$caregiver || !$caregiver->isCaregiver() || $patient->caregiver_id !== $caregiver->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $defaults = config('alerts.thresholds', []);
        $customs = PatientAlertThreshold::where('elderly_id', $patient->id)->get()->keyBy('metric_type');

        $result = [];
        foreach ($defaults as $metricType => $defaultValues) {
            $customRecord = $customs->get($metricType);
            $result[$metricType] = [
                'is_custom' => $customRecord !== null,
                'thresholds' => $customRecord ? $customRecord->thresholds : $defaultValues,
                'default_thresholds' => $defaultValues,
            ];
        }

        return response()->json([
            'success' => true,
            'patient_id' => $patient->id,
            'metrics' => $result,
        ]);
    }

    /**
     * Update/override alert thresholds for a specific patient.
     */
    public function update(Request $request, UserProfile $patient): JsonResponse
    {
        $caregiver = Auth::user()->profile;

        if (!$caregiver || !$caregiver->isCaregiver() || $patient->caregiver_id !== $caregiver->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'metric_type' => 'required|string|in:blood_pressure,sugar_level,temperature,heart_rate',
            'thresholds' => 'required|array',
            'reset_to_default' => 'nullable|boolean',
        ]);

        $metricType = $validated['metric_type'];

        if (!empty($validated['reset_to_default'])) {
            PatientAlertThreshold::where('elderly_id', $patient->id)
                ->where('metric_type', $metricType)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "Thresholds for {$metricType} reset to clinical default.",
                'thresholds' => config("alerts.thresholds.{$metricType}"),
            ]);
        }

        $threshold = PatientAlertThreshold::updateOrCreate(
            [
                'elderly_id' => $patient->id,
                'metric_type' => $metricType,
            ],
            [
                'thresholds' => $validated['thresholds'],
                'created_by' => Auth::id(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Custom thresholds for {$metricType} saved successfully.",
            'threshold' => $threshold,
        ]);
    }
}
