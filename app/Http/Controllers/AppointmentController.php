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
        'duration' => 'required|in:15,20',
        'appointment_time' => [
            'required',
            'date',
            function ($attribute, $value, $fail) {
                if (now()->diffInHours(\Carbon\Carbon::parse($value)) < 2) {
                    $fail('Appointments must be scheduled at least 2 hours in advance.');
                }
            }
        ],
        'reason' => 'required|string|max:500'
    ]);

    // Check doctor availability
    $conflictingAppointments = Appointment::where('doctor_id', $validated['doctor_id'])
        ->where('appointment_time', '<=', \Carbon\Carbon::parse($validated['appointment_time'])->addMinutes($validated['duration']))
        ->where('appointment_time', '>=', \Carbon\Carbon::parse($validated['appointment_time'])->subMinutes($validated['duration']))
        ->exists();

    if ($conflictingAppointments) {
        return response()->json([
            'success' => false,
            'message' => 'Doctor is not available at the selected time'
        ], 409);
    }

    // Rest of your payment and creation logic...
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
    $appointment = Appointment::where('payment_reference', $referenceId)
        ->with(['student', 'doctor'])
        ->firstOrFail();

    // Only check with MoMo if status is still pending
    if ($appointment->status === 'pending_payment') {
        $status = $this->momoService->getPaymentStatus($referenceId);
        
        if ($status && $status['status'] === 'SUCCESSFUL') {
            $appointment->update(['status' => 'confirmed']);
            $this->sendAppointmentConfirmation($appointment);
        } elseif ($status && $status['status'] === 'FAILED') {
            $appointment->update(['status' => 'payment_failed']);
        }
    }

    return response()->json([
        'status' => $appointment->status,
        'appointment' => $appointment
    ]);
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