<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\School;
use App\Models\HealthFacility;
use App\Models\Doctor;
use App\Models\Student;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    public function store(Request $request)
{
    \Log::info('Appointment request data:', $request->all());

    // Validate request
    $validator = Validator::make($request->all(), [
        'doctor_id' => 'required|exists:doctors,id',
        'duration' => 'required|in:15,20,30,45,60',
        'appointment_time' => [
            'required',
            'date',
            function ($attribute, $value, $fail) {
                if (now()->diffInHours(Carbon::parse($value)) < 1) {
                    $fail('Appointments must be scheduled at least 1 hour in advance.');
                }
            }
        ],
        'reason' => 'required|string|max:500',
        'health_facility_id' => 'required_without:school_id|exists:health_facilities,id',
        'patient_id' => 'required_with:health_facility_id|exists:patients,id',
        'school_id' => 'required_without:health_facility_id|exists:schools,id',
        'student_id' => 'required_with:school_id|exists:students,id'
    ]);

    // Additional validation
    $validator->after(function ($validator) use ($request) {
        if ($request->filled('health_facility_id')) {
            $patient = Patient::find($request->patient_id);
            if ($patient && $patient->health_facility_id != $request->health_facility_id) {
                $validator->errors()->add('patient_id', 'Patient does not belong to this health facility');
            }
        }
        
        if ($request->filled('school_id')) {
            $student = Student::find($request->student_id);
            if ($student && $student->school_id != $request->school_id) {
                $validator->errors()->add('student_id', 'Student does not belong to this school');
            }
        }
    });

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    // Create appointment
    try {
        $appointment = Appointment::create([
            'doctor_id' => $request->doctor_id,
            'appointment_time' => $request->appointment_time,
            'duration' => (int)$request->duration,
            'reason' => $request->reason,
            'status' => 'confirmed',
            'health_facility_id' => $request->health_facility_id,
            'patient_id' => $request->patient_id,
            'school_id' => $request->school_id,
            'student_id' => $request->student_id
        ]);

        return response()->json([
            'success' => true,
            'data' => $appointment
        ], 201);

    } catch (\Exception $e) {
        \Log::error('Appointment creation failed: '.$e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Appointment creation failed'
        ], 500);
    }
}
    public function index(Request $request)
    {
        $query = Appointment::query();
        
        if ($request->has('school_id')) {
            $query->where('school_id', $request->school_id)
                  ->with(['student', 'doctor']);
        } 
        elseif ($request->has('health_facility_id')) {
            $query->where('health_facility_id', $request->health_facility_id)
                  ->with(['patient', 'doctor']);
        }
        else {
            return response()->json([
                'success' => false,
                'message' => 'Must specify school_id or health_facility_id'
            ], 400);
        }

        $appointments = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $appointments
        ]);
    }

    public function checkStatus($referenceId)
    {
        $appointment = Appointment::where('payment_reference', $referenceId)
            ->with(['student', 'patient', 'doctor'])
            ->firstOrFail();

        return response()->json([
            'status' => $appointment->status,
            'appointment' => $appointment
        ]);
    }

    protected function sendAppointmentConfirmation(Appointment $appointment)
    {
        $user = $appointment->student ?? $appointment->patient;
        $institution = $appointment->school ?? $appointment->healthFacility;
        $doctor = $appointment->doctor;
        
        $message = "Appointment Confirmed:\n\n" .
                   ($appointment->student ? "Student" : "Patient") . ": {$user->name}\n" .
                   "Doctor: Dr. {$doctor->name}\n" .
                   "Type: " . ($doctor->specialization === 'General Practitioner' ? 'General' : 'Specialist') . "\n" .
                   "Duration: {$appointment->duration} mins\n" .
                   "Time: {$appointment->appointment_time->format('D, M j, Y g:i A')}\n" .
                   "Reason: {$appointment->reason}";

        // Send to appropriate contacts
        if ($appointment->student && $appointment->student->parent_contact) {
            $this->sendSms($appointment->student->parent_contact, $message);
        }
        elseif ($appointment->patient && $appointment->patient->contact_number) {
            $this->sendSms($appointment->patient->contact_number, $message);
        }

        // Send to institution
        if ($institution && $institution->contact_number) {
            $this->sendSms($institution->contact_number, $message);
        }
    }

    protected function sendSms($number, $message)
    {
        // SMS sending implementation
        \Log::info('SMS would be sent to: ' . $number, ['message' => $message]);
    }
}