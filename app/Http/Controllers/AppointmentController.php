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
        $validator = Validator::make($request->all(), [
            // Institution type validation
            'school_id' => 'nullable|required_without:health_facility_id|exists:schools,id',
            'health_facility_id' => 'nullable|required_without:school_id|exists:health_facilities,id',
            
            // User type validation
            'student_id' => 'nullable|required_without:patient_id|exists:students,id',
            'patient_id' => 'nullable|required_without:student_id|exists:patients,id',
            
            // Common fields
            'doctor_id' => 'required|exists:doctors,id',
            'duration' => 'required|in:15,20,30,45,60',
            'appointment_time' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if (now()->diffInHours(Carbon::parse($value)) < 2) {
                        $fail('Appointments must be scheduled at least 2 hours in advance.');
                    }
                }
            ],
            'reason' => 'required|string|max:500'
        ]);

        // Custom validation for institution-user relationship
        $validator->after(function ($validator) use ($request) {
            if ($request->has('school_id') && $request->has('patient_id')) {
                $validator->errors()->add('system', 'Cannot mix school with patient');
            }
            if ($request->has('health_facility_id') && $request->has('student_id')) {
                $validator->errors()->add('system', 'Cannot mix health facility with student');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Check doctor availability
        $conflictingAppointments = Appointment::where('doctor_id', $validated['doctor_id'])
            ->where('appointment_time', '<=', Carbon::parse($validated['appointment_time'])->addMinutes($validated['duration']))
            ->where('appointment_time', '>=', Carbon::parse($validated['appointment_time'])->subMinutes($validated['duration']))
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
        if ($request->has('school_id')) {
            $appointmentData['school_id'] = $validated['school_id'];
            $appointmentData['student_id'] = $validated['student_id'];
        } else {
            $appointmentData['health_facility_id'] = $validated['health_facility_id'];
            $appointmentData['patient_id'] = $validated['patient_id'];
        }

        $appointment = Appointment::create($appointmentData);

        return response()->json([
            'success' => true,
            'message' => 'Appointment booked successfully',
            'data' => $appointment
        ]);
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

        return $query->latest()->get();
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
                   "Amount: " . number_format($appointment->amount) . " UGX\n" .
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
        if ($institution->contact_number) {
            $this->sendSms($institution->contact_number, $message);
        }
    }

    protected function sendSms($number, $message)
    {
        // SMS sending implementation
    }
}