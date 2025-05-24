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
    protected $momoService;

    public function __construct(MomoService $momoService)
    {
        $this->momoService = $momoService;
    }

    public function store(Request $request)
    {
        // Debug log to see what's being sent
        \Log::info('Appointment request data:', $request->all());

        $validator = Validator::make($request->all(), [
            // Institution type validation - only one required
            'school_id' => 'nullable|exists:schools,id',
            'health_facility_id' => 'nullable|exists:health_facilities,id',
            
            // User type validation - only one required
            'student_id' => 'nullable|exists:students,id',
            'patient_id' => 'nullable|exists:patients,id',
            
            // Common required fields
            'doctor_id' => 'required|exists:doctors,id',
            'duration' => 'required|in:15,20,30,45,60',
            'appointment_time' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if (now()->diffInHours(Carbon::parse($value)) < 1) { // Changed from 2 to 1 hour
                        $fail('Appointments must be scheduled at least 1 hour in advance.');
                    }
                }
            ],
            'reason' => 'required|string|max:500'
        ]);

        // Custom validation logic
        $validator->after(function ($validator) use ($request) {
            // Must have either school_id or health_facility_id
            if (!$request->filled('school_id') && !$request->filled('health_facility_id')) {
                $validator->errors()->add('institution', 'Either school_id or health_facility_id is required');
            }

            // Must have either student_id or patient_id
            if (!$request->filled('student_id') && !$request->filled('patient_id')) {
                $validator->errors()->add('user', 'Either student_id or patient_id is required');
            }

            // Logical combinations
            if ($request->filled('school_id') && $request->filled('patient_id')) {
                $validator->errors()->add('combination', 'Cannot use patient_id with school_id');
            }
            if ($request->filled('health_facility_id') && $request->filled('student_id')) {
                $validator->errors()->add('combination', 'Cannot use student_id with health_facility_id');
            }

            // If health facility, must have patient
            if ($request->filled('health_facility_id') && !$request->filled('patient_id')) {
                $validator->errors()->add('patient_id', 'Patient is required for health facility appointments');
            }

            // If school, must have student
            if ($request->filled('school_id') && !$request->filled('student_id')) {
                $validator->errors()->add('student_id', 'Student is required for school appointments');
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
        $endTime = $appointmentTime->copy()->addMinutes($validated['duration']);
        $startTime = $appointmentTime->copy()->subMinutes($validated['duration']);

        $conflictingAppointments = Appointment::where('doctor_id', $validated['doctor_id'])
            ->where('status', '!=', 'cancelled')
            ->where(function($query) use ($appointmentTime, $endTime, $startTime) {
                $query->whereBetween('appointment_time', [$startTime, $endTime])
                      ->orWhere(function($q) use ($appointmentTime) {
                          $q->where('appointment_time', '<=', $appointmentTime)
                            ->whereRaw('DATE_ADD(appointment_time, INTERVAL duration MINUTE) > ?', [$appointmentTime]);
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
            'duration' => $validated['duration'],
            'reason' => $validated['reason'],
            'status' => 'confirmed'
        ];

        // Set institution and user data
        if ($request->filled('school_id')) {
            $appointmentData['school_id'] = $validated['school_id'];
            $appointmentData['student_id'] = $validated['student_id'];
        } else {
            $appointmentData['health_facility_id'] = $validated['health_facility_id'];
            $appointmentData['patient_id'] = $validated['patient_id'];
        }

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

        // Only check with MoMo if status is still pending
        if ($appointment->status === 'pending_payment') {
            $status = $this->momoService->getPaymentStatus($referenceId);
            
            if ($status && $status['status'] === 'SUCCESSFUL') {
                $appointment->update(['status' => 'confirmed']);
                $this->sendAppointmentConfirmation($appointment);
            } 
            elseif ($status && $status['status'] === 'FAILED') {
                $appointment->update(['status' => 'payment_failed']);
            }
        }

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
                   "Amount: " . number_format($appointment->amount ?? 0) . " UGX\n" .
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