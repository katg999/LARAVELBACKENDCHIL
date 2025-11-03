<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\HealthFacility;
use App\Models\MedicalHistory;
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
        $patients = Patient::forHealthFacility($id)->get();

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
        
        return view('maternal.maternal-documents', [
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
                    if ($patient->health_facility_id == $existingValidation['health_facility_id'] || $patient->healthFacilities()->where('health_facility_id', $existingValidation['health_facility_id'])->exists()) {
                        return redirect()->back()
                            ->with('error', 'Patient is already associated with this health facility.')
                            ->withInput();
                    }
                    // Use update method to properly handle many-to-many association
                    $patient->update([
                        'health_facility_id' => $existingValidation['health_facility_id'],
                        'medical_history' => $existingValidation['medical_history'] ?? $patient->medical_history
                    ]);
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
                    'grade' => $newValidation['grade'] ?? null,
                    'parent_contact' => $newValidation['parent_contact'] ?? null,
                ]);

                // Handle health facility association after creation
                if (!empty($newValidation['health_facility_id'])) {
                    $patient->update(['health_facility_id' => $newValidation['health_facility_id']]);
                }

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
            ->route('health-facility.patients', ['id' => $facilityId])
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

    /**
     * Store a medical history note for a patient.
     *
     * @param  \App\Models\Patient  $patient
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeMedicalHistory(Patient $patient, Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'recorded_date' => 'nullable|date',
        ]);

        // Get the current authenticated user (assuming it's a doctor)
        $doctor = auth()->user();

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to add medical notes.'
            ], 401);
        }

        // Check if the doctor exists in the doctors table
        $doctorRecord = \App\Models\Doctor::where('user_id', $doctor->id)->first();

        if (!$doctorRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Only doctors can add medical notes.'
            ], 403);
        }

        try {
            \App\Models\MedicalHistory::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctorRecord->id,
                'content' => $request->content,
                'recorded_date' => $request->recorded_date ?: now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Medical note added successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add medical note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific medical history note.
     *
     * @param  \App\Models\Patient  $patient
     * @param  \App\Models\MedicalHistory  $medicalHistory
     * @return \Illuminate\Http\JsonResponse
     */
    public function showMedicalHistory(Patient $patient, MedicalHistory $medicalHistory)
    {
        // Ensure the medical history belongs to the patient
        if ($medicalHistory->patient_id !== $patient->id) {
            return response()->json([
                'error' => 'Medical history does not belong to this patient.'
            ], 403);
        }

        return response()->json([
            'id' => $medicalHistory->id,
            'content' => $medicalHistory->content,
            'recorded_date' => $medicalHistory->recorded_date ? $medicalHistory->recorded_date->format('Y-m-d') : null,
            'doctor' => $medicalHistory->doctor->name ?? 'Unknown',
            'created_at' => $medicalHistory->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a medical history note for a patient.
     *
     * @param  \App\Models\Patient  $patient
     * @param  \App\Models\MedicalHistory  $medicalHistory
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMedicalHistory(Patient $patient, MedicalHistory $medicalHistory, Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'recorded_date' => 'nullable|date',
        ]);

        // Ensure the medical history belongs to the patient
        if ($medicalHistory->patient_id !== $patient->id) {
            return response()->json([
                'success' => false,
                'message' => 'Medical history does not belong to this patient.'
            ], 403);
        }

        // Get the current authenticated user (assuming it's a doctor)
        $doctor = auth()->user();

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to edit medical notes.'
            ], 401);
        }

        // Check if the doctor exists in the doctors table
        $doctorRecord = \App\Models\Doctor::where('user_id', $doctor->id)->first();

        if (!$doctorRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Only doctors can edit medical notes.'
            ], 403);
        }

        // Ensure the doctor is the one who created the note (or allow admin to edit)
        if ($medicalHistory->doctor_id !== $doctorRecord->id && !$doctor->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit medical notes you created.'
            ], 403);
        }

        try {
            $medicalHistory->update([
                'content' => $request->content,
                'recorded_date' => $request->recorded_date ?: $medicalHistory->recorded_date,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Medical note updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update medical note: ' . $e->getMessage()
            ], 500);
        }
    }
}
