<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\HealthFacility;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Get all patients for a specific health facility.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getPatientsByHealthFacility($id)
    {
        // Fetch patients based on health facility ID
        $patients = Patient::where('health_facility_id', $id)->get();

        // Return the data to the view or as JSON
        return view('Health-Facility-Instance', compact('patients'));
    }

    /**
     * Show the form for creating a new patient.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // You can pass health facilities to the create view for selection
        $healthFacilities = HealthFacility::all();
        return view('patients.create', compact('healthFacilities'));
    }

    /**
     * Store a newly created patient in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:patients,email',
            'phone' => 'required|string|max:15',
            'health_facility_id' => 'required|exists:health_facilities,id',
        ]);

        // Create the new patient
        Patient::create($validated);

        // Redirect back to the patients list for this health facility
        return redirect()->route('patients.index', ['id' => $validated['health_facility_id']])
                         ->with('success', 'Patient added successfully!');
    }
}
