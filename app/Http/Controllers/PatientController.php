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


    public function maternalDocuments(Patient $patient)
    {
        $documents = $patient->maternalDocuments()->orderBy('created_at', 'desc')->get();
        $groupedDocuments = $documents->groupBy('document_type');
        
        return view('maternal-documents', [
            'patient' => $patient,
            'groupedDocuments' => $groupedDocuments,
            'healthFacility' => $patient->healthFacility // Get facility from patient instead
        ]);
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
           'health_facility_id',
        'name',
        'gender',
        'birth_date',
        'contact_number',
        'medical_history'
        ]);

        // Create the new patient
        Patient::create($validated);

        // Redirect back to the patients list for this health facility
        return redirect()->route('patients.index', ['id' => $validated['health_facility_id']])
                         ->with('success', 'Patient added successfully!');
    }
}
