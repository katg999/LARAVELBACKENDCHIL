<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HealthFacility;
use Illuminate\Support\Facades\Log;
use App\Models\Message;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use Carbon\Carbon;


class HealthFacilityController extends Controller
{
    /**
     * Register a new health facility
     */
    public function registerHealthFacility(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:health_facilities',
                'contact' => 'required|string|max:20',
                'file_url' => 'nullable|string'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error validating health facility: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error validating health facility data',
                'errors' => $e->errors()
            ], 422);
        }

        $healthFacility = HealthFacility::create($validated);

        return response()->json([
            'message' => 'Health facility registered successfully',
            'health_facility' => $healthFacility
        ], 201);
    }

    /**
     * Get all health facilities
     */
   public function getHealthFacilities()
{
    $healthFacilities = HealthFacility::all();
    
    return response()->json([
        'success' => true,
        'data' => $healthFacilities
    ]);
}

    /**
     * Update file URL for most recently created health facility
     */
    public function updateLatestHealthFacilityFile(Request $request)
    {
        $healthFacility = HealthFacility::latest()->first();

        if (!$healthFacility) {
            return response()->json([
                'message' => 'No health facility records found to update'
            ], 404);
        }

        $validated = $request->validate([
            'file_url' => 'required|string|url'
        ]);

        $healthFacility->update(['file_url' => $validated['file_url']]);

        Log::info('Latest health facility file URL updated', [
            'health_facility_id' => $healthFacility->id,
            'file_url' => $validated['file_url']
        ]);

        return response()->json([
            'message' => 'File URL updated for most recent health facility',
            'health_facility_id' => $healthFacility->id,
            'file_url' => $healthFacility->file_url
        ]);
    }

    /**
     * Update specific health facility by ID
     */
    public function updateHealthFacility(Request $request, $id)
    {
        $healthFacility = HealthFacility::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:health_facilities,email,'.$healthFacility->id,
            'contact' => 'sometimes|string|max:20',
            'file_url' => 'nullable|string'
        ]);

        $healthFacility->update($validated);

        return response()->json([
            'message' => 'Health facility updated successfully',
            'health_facility' => $healthFacility
        ]);
    }

    public function showDashboard(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $healthFacility = HealthFacility::findOrFail($authenticatedUser['id']);
    
        $notifications = collect(); 
    
        $allDoctors = Doctor::all(); // <-- fetch all doctors in the DB
    
        $unreadMessages = Message::where('health_facility_id', $healthFacility->id)
                                 ->where('is_read', false)
                                 ->count();
    
        $appointments = Appointment::where('health_facility_id', $healthFacility->id)
                                   ->latest()
                                   ->take(10)
                                   ->get();
    
        $availableDoctors = Doctor::where('health_facility_id', $healthFacility->id)
                                  ->whereHas('availabilities', function ($query) {
                                      $query->where('available', true);
                                  })
                                  ->get();
    
        $messages = Message::where('health_facility_id', $healthFacility->id)
                           ->orderBy('created_at', 'desc')
                           ->get();
    
        $patients = Patient::forHealthFacility($healthFacility->id)->get();

        // Metrics
        $patientsCount = $patients->count();
        $appointmentsCount = Appointment::where('health_facility_id', $healthFacility->id)->count();
        $availableDoctorsCount = Doctor::where('health_facility_id', $healthFacility->id)
            ->whereHas('availabilities', function ($query) {
                $query->where('available', true);
            })->count();

        // Build last 7 days series (rolling window including today)
        $labels = [];
        $series = [];
        $startDay = Carbon::now()->subDays(6)->startOfDay();
        for ($i = 0; $i < 7; $i++) {
            $day = (clone $startDay)->addDays($i);
            $labels[] = $day->format('D');
            $dayStart = (clone $day)->startOfDay();
            $dayEnd = (clone $day)->endOfDay();
            $countForDay = Appointment::where('health_facility_id', $healthFacility->id)
                ->whereBetween('appointment_time', [$dayStart, $dayEnd])
                ->count();
            $series[] = $countForDay;
        }

        // Doughnut: Patients by gender
        $maleCount = Patient::forHealthFacility($healthFacility->id)->where('gender', 'male')->count();
        $femaleCount = Patient::forHealthFacility($healthFacility->id)->where('gender', 'female')->count();
        $otherCount = Patient::forHealthFacility($healthFacility->id)->where('gender', 'other')->count();
        $unknownCount = Patient::forHealthFacility($healthFacility->id)
            ->where(function ($q) { $q->whereNull('gender')->orWhere('gender', ''); })
            ->count();
        $genderLabels = ['Male', 'Female', 'Other', 'Unspecified'];
        $genderData = [$maleCount, $femaleCount, $otherCount, $unknownCount];
    
        return view('health-facility.dashboard', [
            'healthFacility' => $healthFacility,
            'unreadMessages' => $unreadMessages,
            'allDoctors' => $allDoctors,
            'appointments' => $appointments,
            'availableDoctors' => $availableDoctors,
            'patients' => $patients,
            'messages' => $messages,
            'stats' => [
                'patients' => $patientsCount,
                'appointments' => $appointmentsCount,
                'availableDoctors' => $availableDoctorsCount,
                'unreadMessages' => $unreadMessages,
            ],
            'weeklyLabels' => $labels,
            'weeklyData' => $series,
            'genderLabels' => $genderLabels,
            'genderData' => $genderData,
        ]);
    }

    public function patients(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $healthFacility = HealthFacility::findOrFail($authenticatedUser['id']);
        $patients = Patient::forHealthFacility($healthFacility->id)->latest()->get();
        return view('health-facility/patients', compact('healthFacility', 'patients'));
    }

    public function destroyAllPatients(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $healthFacility = HealthFacility::findOrFail($authenticatedUser['id']);
        
        // Check if a specific patient ID is provided
        if ($request->has('patient_id')) {
            $patient = Patient::findOrFail($request->patient_id);
            
            // Verify the patient belongs to the health facility
            if ($patient->health_facility_id !== $healthFacility->id) {
                abort(403, 'Patient does not belong to this health facility');
            }
            
            // Cascade appointments: delete child appointments before patient
            $patient->appointments()->delete();
            
            $patient->delete();
            
            return redirect()
                ->route('health-facility.patients')
                ->with('success', 'Patient and their appointments deleted successfully.');
        } else {
            // Delete all patients for this health facility
            $patients = Patient::forHealthFacility($healthFacility->id)->get();
            
            // Delete appointments for each patient first
            foreach ($patients as $patient) {
                $patient->appointments()->delete();
            }
            
            // Delete all patients
            Patient::forHealthFacility($healthFacility->id)->delete();
            
            return redirect()
                ->route('health-facility.patients')
                ->with('success', 'All patients and their appointments deleted successfully.');
        }
    }

    public function destroyPatient(Request $request, $patientId)
    {
        $authenticatedUser = $request->current_user;
        $healthFacility = HealthFacility::findOrFail($authenticatedUser['id']);

        Log::info('Destroy patient called', ['health_facility_id' => $healthFacility->id, 'patient_id_param' => $patientId]);

        $patient = Patient::findOrFail($patientId);
        Log::info('Patient found', ['patient_id' => $patient->id, 'patient_health_facility_id' => $patient->health_facility_id]);

        // Verify the patient belongs to the health facility
        if ($patient->health_facility_id !== $healthFacility->id) {
            Log::warning('Patient does not belong to health facility', ['patient_health_facility_id' => $patient->health_facility_id, 'requested_id' => $healthFacility->id]);
            abort(403, 'Patient does not belong to this health facility');
        }

        // Cascade appointments: delete child appointments before patient
        $patient->appointments()->delete();

        $patient->delete();

        return redirect()
            ->route('health-facility.patients')
            ->with('success', 'Patient and their appointments deleted successfully.');
    }

    public function createPatient(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $healthFacility = HealthFacility::findOrFail($authenticatedUser['id']);
        return view('health-facility/patients-create', compact('healthFacility'));
    }

    public function bookDoctor(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $healthFacility = HealthFacility::findOrFail($authenticatedUser['id']);
        $patients = Patient::forHealthFacility($healthFacility->id)->latest()->get();
        $doctors = Doctor::latest()->get();
        $appointments = Appointment::where('health_facility_id', $healthFacility->id)
            ->with(['patient', 'doctor', 'duration'])
            ->latest()
            ->get();
        return view('health-facility/book-doctor', compact('healthFacility', 'patients', 'doctors', 'appointments'));
    }

    public function labTests(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $healthFacility = HealthFacility::findOrFail($authenticatedUser['id']);
        // Placeholder: if LabTest supports health_facility_id, filter; else show empty list
        $labTests = collect();
        return view('health-facility/lab-tests', compact('healthFacility', 'labTests'));
    }

    public function transactions(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $healthFacility = HealthFacility::findOrFail($authenticatedUser['id']);
        // Get transactions/payments related to this health facility
        // For now, we'll show appointments with payment status
        $appointments = Appointment::where('health_facility_id', $healthFacility->id)
            ->with(['patient', 'doctor', 'duration'])
            ->latest()
            ->paginate(15);
        return view('health-facility/transactions', compact('healthFacility', 'appointments'));
    }

    public function staff(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $healthFacility = HealthFacility::findOrFail($authenticatedUser['id']);
        // Get doctors associated with this health facility
        $doctors = Doctor::where('health_facility_id', $healthFacility->id)->latest()->get();
        return view('health-facility/staff', compact('healthFacility', 'doctors'));
    }

    
}