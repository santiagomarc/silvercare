<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientListController extends Controller
{
    public function index()
    {
        $caregiver = Auth::user()->profile;

        if (!$caregiver || !$caregiver->isCaregiver()) {
            abort(403);
        }

        $activePatients = \App\Models\UserProfile::where('caregiver_id', $caregiver->id)
            ->whereNull('archived_at')
            ->with('user')
            ->get()
            ->map(function ($patient) {
                return $this->buildPatientData($patient, 'active');
            });

        $archivedPatients = \App\Models\UserProfile::where('caregiver_id', $caregiver->id)
            ->whereNotNull('archived_at')
            ->with('user')
            ->get()
            ->map(function ($patient) {
                return $this->buildPatientData($patient, 'archived');
            });

        return view('caregiver.patients.index', compact('activePatients', 'archivedPatients'));
    }

    public function remove(Request $request, $patientId)
    {
        $caregiver = Auth::user()->profile;

        if (!$caregiver || !$caregiver->isCaregiver()) {
            abort(403);
        }

        $patient = \App\Models\UserProfile::where('id', $patientId)
            ->where('caregiver_id', $caregiver->id)
            ->whereNull('archived_at')
            ->firstOrFail();

        $patient->update([
            'archived_at' => now(),
            'archived_by_caregiver_id' => $caregiver->id,
        ]);

        return back()->with('success', $patient->user?->name . ' has been removed and their records have been archived.');
    }

    public function restore($patientId)
    {
        $caregiver = Auth::user()->profile;

        if (!$caregiver || !$caregiver->isCaregiver()) {
            abort(403);
        }

        $patient = \App\Models\UserProfile::where('id', $patientId)
            ->where('caregiver_id', $caregiver->id)
            ->whereNotNull('archived_at')
            ->firstOrFail();

        $patient->update([
            'archived_at' => null,
            'archived_by_caregiver_id' => null,
            'caregiver_id' => $caregiver->id,
        ]);

        return back()->with('success', $patient->user?->name . ' has been restored to your active patients.');
    }

    private function buildPatientData($patient, $status)
    {
        $lastVital = \App\Models\HealthMetric::where('elderly_id', $patient->id)
            ->latest('measured_at')
            ->first();

        $todayMeds = \App\Models\Medication::where('elderly_id', $patient->id)
            ->where('is_active', true)
            ->count();

        $takenToday = \App\Models\MedicationLog::whereHas('medication', fn($q) => $q->where('elderly_id', $patient->id))
            ->whereDate('scheduled_time', today())
            ->where('is_taken', true)
            ->count();

        $adherence = $todayMeds > 0 ? round(($takenToday / $todayMeds) * 100) : null;

        return [
            'profile' => $patient,
            'user' => $patient->user,
            'status' => $status,
            'last_active' => $lastVital?->measured_at ?? $patient->updated_at,
            'medication_adherence' => $adherence,
            'archived_at' => $patient->archived_at,
        ];
    }
}