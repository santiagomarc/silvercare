<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $caregiver = Auth::user()->profile;
        $elderly = $caregiver->elderly;

        if (!$elderly) {
            return redirect()->route('caregiver.dashboard')->with('error', 'No elderly profile associated.');
        }

        $medications = $elderly->trackedMedications()->orderBy('created_at', 'desc')->get();

        return view('caregiver.medications.index', compact('medications'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('caregiver.medications.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'dosage' => 'required|string|max:50',
            'dosage_unit' => 'nullable|string|max:20',
            'instructions' => 'nullable|string|max:1000',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'times_of_day' => 'required|array|min:1',
            'times_of_day.*' => 'string|date_format:H:i',
            'start_date' => 'nullable|date',
        ]);

        $caregiver = Auth::user()->profile;
        $elderly = $caregiver->elderly;

        if (!$elderly) {
            return redirect()->route('caregiver.dashboard')->with('error', 'No elderly profile associated.');
        }

        Medication::create([
            'elderly_id' => $elderly->id,
            'caregiver_id' => $caregiver->id,
            'name' => $request->name,
            'dosage' => $request->dosage,
            'dosage_unit' => $request->dosage_unit ?? 'mg',
            'instructions' => $request->instructions,
            'days_of_week' => $request->days_of_week,
            'times_of_day' => $request->times_of_day,
            'start_date' => $request->start_date ?? now(),
            'is_active' => true,
            'track_inventory' => $request->has('track_inventory'),
            'current_stock' => $request->current_stock ?? 0,
            'low_stock_threshold' => $request->low_stock_threshold ?? 5,
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
    public function edit(string $id)
    {
        $medication = Medication::findOrFail($id);
        // Ensure ownership
        if ($medication->caregiver_id !== Auth::user()->profile->id) {
            abort(403);
        }
        return view('caregiver.medications.edit', compact('medication'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $medication = Medication::findOrFail($id);
        
        if ($medication->caregiver_id !== Auth::user()->profile->id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'dosage' => 'required|string|max:50',
            'dosage_unit' => 'nullable|string|max:20',
            'instructions' => 'nullable|string|max:1000',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'times_of_day' => 'required|array|min:1',
            'times_of_day.*' => 'string|date_format:H:i',
        ]);

        $medication->update([
            'name' => $request->name,
            'dosage' => $request->dosage,
            'dosage_unit' => $request->dosage_unit ?? 'mg',
            'instructions' => $request->instructions,
            'days_of_week' => $request->days_of_week,
            'times_of_day' => $request->times_of_day,
            'track_inventory' => $request->has('track_inventory'),
            'current_stock' => $request->current_stock,
            'low_stock_threshold' => $request->low_stock_threshold ?? 5,
        ]);

        return redirect()->route('caregiver.medications.index')->with('success', 'Medication updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $medication = Medication::findOrFail($id);
        if ($medication->caregiver_id !== Auth::user()->profile->id) {
            abort(403);
        }
        $medication->delete();
        return redirect()->route('caregiver.medications.index')->with('success', 'Medication deleted successfully.');
    }
}
