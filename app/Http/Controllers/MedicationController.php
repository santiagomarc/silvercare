<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesElderlyPatient;
use App\Models\Medication;
use App\Http\Requests\StoreMedicationRequest;
use App\Http\Requests\UpdateMedicationRequest;
use App\Services\MedicationService;
use Illuminate\Support\Facades\Auth;

class MedicationController extends Controller
{
    use ResolvesElderlyPatient;

    public function __construct(protected MedicationService $medicationService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Medication::class);

        $caregiver = Auth::user()->profile;
        $elderlyPatients = $this->caregiverPatients($caregiver);
        $selectedElderly = $this->resolveSelectedPatient($elderlyPatients, request()->integer('elderly'));

        if (!$selectedElderly) {
            return redirect()->route('caregiver.dashboard')->with('error', 'No elderly profile associated.');
        }

        $medications = $this->medicationService->getMedicationSchedulesForElderly($selectedElderly->id);

        return view('caregiver.medications.index', compact('medications', 'elderlyPatients', 'selectedElderly'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Medication::class);

        $caregiver = Auth::user()->profile;
        $elderlyPatients = $this->caregiverPatients($caregiver);
        $selectedElderly = $this->resolveSelectedPatient($elderlyPatients, request()->integer('elderly'));

        if (!$selectedElderly) {
            return redirect()->route('caregiver.dashboard')->with('error', 'No elderly profile associated.');
        }

        return view('caregiver.medications.create', compact('elderlyPatients', 'selectedElderly'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMedicationRequest $request)
    {
        $this->authorize('create', Medication::class);

        $caregiver = Auth::user()->profile;
        $elderlyPatients = $this->caregiverPatients($caregiver);
        $selectedElderly = $this->resolveSelectedPatient($elderlyPatients, $request->integer('elderly_id'));

        if (!$selectedElderly) {
            return redirect()->route('caregiver.dashboard')->with('error', 'No elderly profile associated.');
        }

        $this->medicationService->addMedicationSchedule([
            'elderly_id' => $selectedElderly->id,
            'caregiver_id' => $caregiver->id,
            ...$request->validated(),
            'start_date' => $request->validated('start_date') ?? now(),
            'is_active' => true,
            'track_inventory' => $request->boolean('track_inventory'),
            'current_stock' => $request->integer('current_stock'),
            'low_stock_threshold' => $request->integer('low_stock_threshold') ?: 5,
        ]);

        return redirect()->route('caregiver.medications.index', ['elderly' => $selectedElderly->id])
            ->with('success', 'Medication added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Medication $medication)
    {
        $this->authorize('update', $medication);

        $caregiver = Auth::user()->profile;
        $elderlyPatients = $this->caregiverPatients($caregiver);
        $selectedElderly = $this->resolveSelectedPatient($elderlyPatients, (int) $medication->elderly_id);

        return view('caregiver.medications.edit', compact('medication', 'elderlyPatients', 'selectedElderly'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedicationRequest $request, Medication $medication)
    {
        $this->authorize('update', $medication);

        $payload = $request->validated();

        if ($request->has('track_inventory')) {
            $payload['track_inventory'] = $request->boolean('track_inventory');
        }

        if ($request->has('current_stock')) {
            $payload['current_stock'] = $request->integer('current_stock');
        }

        if ($request->has('low_stock_threshold')) {
            $payload['low_stock_threshold'] = $request->integer('low_stock_threshold') ?: 5;
        }

        $this->medicationService->updateMedicationSchedule($medication, $payload);

        $selectedElderlyId = $request->integer('elderly_id') ?: $medication->elderly_id;

        return redirect()->route('caregiver.medications.index', ['elderly' => $selectedElderlyId])
            ->with('success', 'Medication updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Medication $medication)
    {
        $this->authorize('delete', $medication);

        $selectedElderlyId = request()->integer('elderly_id') ?: $medication->elderly_id;

        $this->medicationService->deleteMedicationSchedule($medication->id);

        return redirect()->route('caregiver.medications.index', ['elderly' => $selectedElderlyId])
            ->with('success', 'Medication deleted successfully.');
    }

}
