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
     * Show the form for creating a new general patient.
     *
     * @return \Illuminate\Http\Response
     */
    public function createGeneral()
    {
        $schools = \App\Models\School::all();
        $healthFacilities = \App\Models\HealthFacility::all();
        return view('patients.create', compact('schools', 'healthFacilities'));
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
     * Store a newly created general patient in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeGeneral(Request $request)
    {
        try {
            $validated = $request->validate([
                'patient_type' => 'required|in:new,existing',
            ]);

            if ($validated['patient_type'] === 'existing') {
                // Handle existing patient association
                $existingValidation = $request->validate([
                    'patient_id' => 'required|string|exists:patients,patient_id',
                    'school_id' => 'nullable|exists:schools,id',
                    'health_facility_id' => 'nullable|exists:health_facilities,id',
                    'grade' => 'nullable|string',
                    'medical_history' => 'nullable|string',
                ]);

                $patient = Patient::where('patient_id', $existingValidation['patient_id'])->first();

                // Prepare update data
                $updateData = [];

                if (!empty($existingValidation['school_id'] ?? null)) {
                    // Check if already associated with this school
                    if ($patient->school_id == $existingValidation['school_id']) {
                        return redirect()->back()
                            ->with('error', 'Patient is already associated with this school.')
                            ->withInput();
                    }
                    $updateData['school_id'] = $existingValidation['school_id'];
                    $updateData['grade'] = $existingValidation['grade'] ?? $patient->grade;
                }

                if (!empty($existingValidation['health_facility_id'] ?? null)) {
                    // Check if already associated with this health facility
                    if ($patient->health_facility_id == $existingValidation['health_facility_id']) {
                        return redirect()->back()
                            ->with('error', 'Patient is already associated with this health facility.')
                            ->withInput();
                    }
                    $updateData['health_facility_id'] = $existingValidation['health_facility_id'];
                    $updateData['medical_history'] = $existingValidation['medical_history'] ?? $patient->medical_history;
                }

                if (!empty($updateData)) {
                    $patient->update($updateData);
                }

                return redirect()->route('home')
                    ->with('success', 'Existing patient associated successfully.');

            } else {
                // Handle new patient creation
                $newValidation = $request->validate([
                    'name' => 'required|string|max:255',
                    'gender' => 'required|in:male,female,other',
                    'birth_date' => 'required|date',
                    'contact_number' => 'nullable|string',
                    'medical_history' => 'nullable|string',
                    'school_id' => 'nullable|exists:schools,id',
                    'health_facility_id' => 'nullable|exists:health_facilities,id',
                    'grade' => 'nullable|string',
                    'parent_contact' => 'nullable|string',
                ]);

                // Create the patient
                $patient = Patient::create([
                    'name' => $newValidation['name'],
                    'gender' => $newValidation['gender'],
                    'birth_date' => $newValidation['birth_date'],
                    'contact_number' => $newValidation['contact_number'],
                    'medical_history' => $newValidation['medical_history'] ?? null,
                    'school_id' => $newValidation['school_id'] ?? null,
                    'health_facility_id' => $newValidation['health_facility_id'] ?? null,
                    'grade' => $newValidation['grade'] ?? null,
                    'parent_contact' => $newValidation['parent_contact'] ?? null,
                ]);

                return redirect()->route('home')
                    ->with('success', 'Patient created successfully.');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Delete a patient, preventing deletion if there are existing appointments.
     */
    public function destroy(Patient $patient)
    {
        $facilityId = $patient->health_facility_id;

        // Cascade appointments: delete child appointments before patient
        $patient->appointments()->delete();

        $patient->delete();

        return redirect()
            ->route('health-facility.patients')
            ->with('success', 'Patient and their appointments deleted successfully.');
    }

    /**
     * Show the patient profile with detailed information and medical history.
     *
     * @param  \App\Models\Patient  $patient
     * @return \Illuminate\Http\Response
     */
    public function show(Patient $patient)
    {
        // Load related data
        $patient->load([
            'healthFacility',
            'school',
            'appointments.doctor',
            'appointments.duration',
            'labTests',
            'maternalDocuments',
            'medicalHistories.doctor'
        ]);

        // Determine which sidebar to show based on patient association
        $viewData = ['patient' => $patient];

        if ($patient->school) {
            $viewData['school'] = $patient->school;
        } elseif ($patient->healthFacility) {
            $viewData['healthFacility'] = $patient->healthFacility;
        }

        return view('patients.profile', $viewData);
    }
}
