<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use App\Http\Requests\StoreMedicationRequest;
use App\Http\Requests\UpdateMedicationRequest;
use App\Services\MedicationService;
use Illuminate\Support\Facades\Auth;

class MedicationController extends Controller
{
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
        $elderly = $caregiver->elderly;

        if (!$elderly) {
            return redirect()->route('caregiver.dashboard')->with('error', 'No elderly profile associated.');
        }

        $medications = $this->medicationService->getMedicationSchedulesForElderly($elderly->id);

        return view('caregiver.medications.index', compact('medications'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Medication::class);

        return view('caregiver.medications.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMedicationRequest $request)
    {
        $this->authorize('create', Medication::class);

        $caregiver = Auth::user()->profile;
        $elderly = $caregiver->elderly;

        if (!$elderly) {
            return redirect()->route('caregiver.dashboard')->with('error', 'No elderly profile associated.');
        }

        $this->medicationService->addMedicationSchedule([
            'elderly_id' => $elderly->id,
            'caregiver_id' => $caregiver->id,
            ...$request->validated(),
            'start_date' => $request->validated('start_date') ?? now(),
            'is_active' => true,
            'track_inventory' => $request->boolean('track_inventory'),
            'current_stock' => $request->integer('current_stock'),
            'low_stock_threshold' => $request->integer('low_stock_threshold') ?: 5,
        ]);

        return redirect()->route('caregiver.medications.index')->with('success', 'Medication added successfully.');
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

        return view('caregiver.medications.edit', compact('medication'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedicationRequest $request, Medication $medication)
    {
        $this->authorize('update', $medication);

        $this->medicationService->updateMedicationSchedule($medication, [
            ...$request->validated(),
            'track_inventory' => $request->boolean('track_inventory'),
            'current_stock' => $request->integer('current_stock'),
            'low_stock_threshold' => $request->integer('low_stock_threshold') ?: 5,
        ]);

        return redirect()->route('caregiver.medications.index')->with('success', 'Medication updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Medication $medication)
    {
        $this->authorize('delete', $medication);

        $this->medicationService->deleteMedicationSchedule($medication->id);

        return redirect()->route('caregiver.medications.index')->with('success', 'Medication deleted successfully.');
    }
}
