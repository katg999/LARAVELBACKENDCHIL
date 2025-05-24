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

        // First, determine the context (school or health facility)
        $isHealthFacility = $request->filled('health_facility_id');
        $isSchool = $request->filled('school_id');
        
        // Create dynamic validation rules based on context
        $rules = [
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
            'reason' => 'required|string|max:500'
        ];
        
        // Add context-specific rules
        if ($isHealthFacility) {
            $rules['health_facility_id'] = 'required|exists:health_facilities,id';
            $rules['patient_id'] = 'required|exists:patients,id';
        } elseif ($isSchool) {
            $rules['school_id'] = 'required|exists:schools,id';
            $rules['student_id'] = 'required|exists:students,id';
        }
        
        $validator = Validator::make($request->all(), $rules);

        // Custom validation logic
        $validator->after(function ($validator) use ($request, $isHealthFacility, $isSchool) {
            // Must have exactly one institution type
            if (!$isHealthFacility && !$isSchool) {
                $validator->errors()->add('institution', 'Either school_id or health_facility_id is required');
                return;
            }
            
            if ($isHealthFacility && $isSchool) {
                $validator->errors()->add('institution', 'Cannot specify both school_id and health_facility_id');
                return;
            }
            
            // For health facilities, validate patient relationship
            if ($isHealthFacility) {
                $healthFacilityId = $request->health_facility_id;
                $patientId = $request->patient_id;
                
                $patient = Patient::find($patientId);
                if ($patient && $patient->health_facility_id != $healthFacilityId) {
                    $validator->errors()->add('patient_id', 'Selected patient does not belong to this health facility');
                }
            }
            
            // For schools, validate student relationship
            if ($isSchool) {
                $schoolId = $request->school_id;
                $studentId = $request->student_id;
                
                $student = Student::find($studentId);
                if ($student && $student->school_id != $schoolId) {
                    $validator->errors()->add('student_id', 'Selected student does not belong to this school');
                }
            }
        });

        if ($validator->fails()) {
            \Log::error('Appointment validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Check doctor availability
        $appointmentTime = Carbon::parse($validated['appointment_time']);
        $duration = (int)$validated['duration'];
        $endTime = $appointmentTime->copy()->addMinutes($duration);
        $startTime = $appointmentTime->copy()->subMinutes($duration);

        $conflictingAppointments = Appointment::where('doctor_id', $validated['doctor_id'])
            ->where('status', '!=', 'cancelled')
            ->where(function($query) use ($appointmentTime, $endTime) {
                $query->whereBetween('appointment_time', [$appointmentTime, $endTime])
                      ->orWhere(function($q) use ($appointmentTime, $endTime) {
                          $q->where('appointment_time', '<=', $appointmentTime)
                            ->where('appointment_time', '>=', $endTime);
                      });
            })
            ->exists();

        if ($conflictingAppointments) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor is not available at the selected time'
            ], 409);
        }

        // Prepare appointment data
        $appointmentData = [
            'doctor_id' => $validated['doctor_id'],
            'appointment_time' => $validated['appointment_time'],
            'duration' => $duration,
            'reason' => $validated['reason'],
            'status' => 'confirmed',
            'school_id' => $isSchool ? $validated['school_id'] : null,
            'health_facility_id' => $isHealthFacility ? $validated['health_facility_id'] : null,
            'patient_id' => $isHealthFacility ? $validated['patient_id'] : null,
            'student_id' => $isSchool ? $validated['student_id'] : null
        ];

        try {
            $appointment = Appointment::create($appointmentData);
            
            \Log::info('Appointment created successfully:', ['appointment_id' => $appointment->id]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment booked successfully',
                'data' => $appointment->load(['doctor', 'patient', 'student'])
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Failed to create appointment:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create appointment: ' . $e->getMessage()
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