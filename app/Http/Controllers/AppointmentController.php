<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\School;
use App\Models\Doctor;
use App\Services\MomoService;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    protected $momoService;

    public function __construct(MomoService $momoService)
    {
        $this->momoService = $momoService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'student_id' => 'required|exists:students,id',
            'doctor_id' => 'required|exists:doctors,id',
            'duration' => 'required|in:15,20', // Added duration field
            'appointment_time' => 'required|date',
            'reason' => 'required|string'
        ]);

        // Get school and doctor
        $school = School::find($validated['school_id']);
        $doctor = Doctor::find($validated['doctor_id']);

        // Calculate amount based on doctor type and duration
        $isSpecialist = $doctor->specialization !== 'General Practitioner';
        $amount = $isSpecialist 
            ? ($validated['duration'] == 15 ? 100000 : 150000)
            : ($validated['duration'] == 15 ? 30000 : 45000);

        // Generate unique transaction ID
        $externalId = 'APP-' . now()->format('YmdHis') . '-' . rand(100, 999);

        // Request payment via MTN MoMo
        $paymentResult = $this->momoService->requestToPay(
            $amount,
            $school->contact_number, // Using school's contact number
            $externalId,
            'Payment for doctor appointment',
            'Appointment with Dr. ' . $doctor->name
        );

        if (!$paymentResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Payment initiation failed: ' . ($paymentResult['message'] ?? 'Unknown error')
            ], 400);
        }

        // Create appointment record
        $appointment = Appointment::create([
            'school_id' => $validated['school_id'],
            'student_id' => $validated['student_id'],
            'doctor_id' => $validated['doctor_id'],
            'appointment_time' => $validated['appointment_time'],
            'duration' => $validated['duration'],
            'reason' => $validated['reason'],
            'amount' => $amount,
            'payment_reference' => $paymentResult['reference_id'],
            'status' => 'pending_payment' // Will be updated via callback
        ]);

        return response()->json([
            'success' => true,
            'appointment' => $appointment,
            'payment_reference' => $paymentResult['reference_id'],
            'check_status_url' => route('appointments.check-status', $paymentResult['reference_id'])
        ]);
    }

    public function index(School $school)
    {
        return $school->appointments()
            ->with(['student', 'doctor'])
            ->latest()
            ->get();
    }

    public function checkStatus($referenceId)
    {
        try {
            $status = $this->momoService->getPaymentStatus($referenceId);
            
            // Find and update appointment
            $appointment = Appointment::where('payment_reference', $referenceId)->firstOrFail();
            
            if ($status && $status['status'] === 'SUCCESSFUL') {
                $appointment->update(['status' => 'confirmed']);
                
                // Send notifications
                $this->sendAppointmentConfirmation($appointment);
                
                return response()->json([
                    'status' => 'successful',
                    'appointment' => $appointment
                ]);
            }
            
            return response()->json([
                'status' => $status['status'] ?? 'pending',
                'appointment' => $appointment
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking payment status'
            ], 500);
        }
    }

    public function handleCallback(Request $request)
    {
        \Log::info('MoMo Callback Received:', $request->all());
        
        $referenceId = $request->input('referenceId');
        $status = $request->input('status');
        
        // Update appointment status based on callback
        $appointment = Appointment::where('payment_reference', $referenceId)->first();
        
        if ($appointment) {
            $newStatus = $status === 'SUCCESSFUL' ? 'confirmed' : 'payment_failed';
            $appointment->update(['status' => $newStatus]);
            
            if ($newStatus === 'confirmed') {
                $this->sendAppointmentConfirmation($appointment);
            }
        }
        
        return response()->json(['success' => true]);
    }

    protected function sendAppointmentConfirmation(Appointment $appointment)
    {
        $student = $appointment->student;
        $doctor = $appointment->doctor;
        
        $message = "Appointment Confirmed:\n\n" .
                   "Student: {$student->name}\n" .
                   "Doctor: Dr. {$doctor->name}\n" .
                   "Type: " . ($doctor->specialization === 'General Practitioner' ? 'General' : 'Specialist') . "\n" .
                   "Duration: {$appointment->duration} mins\n" .
                   "Amount: " . number_format($appointment->amount) . " UGX\n" .
                   "Time: {$appointment->appointment_time->format('D, M j, Y g:i A')}\n" .
                   "Reason: {$appointment->reason}\n\n" .
                   "Doctor will share meeting link.";
        
        // Send to parent contact if available
        if ($student->parent_contact) {
            $this->sendSms($student->parent_contact, $message);
        }
        
        // Also send to school
        $this->sendSms($appointment->school->contact_number, $message);
    }
    
    protected function sendSms($number, $message)
    {
        // Implement your SMS sending logic here
        // This could be via a service like MTN SMS API, Twilio, etc.
    }
}