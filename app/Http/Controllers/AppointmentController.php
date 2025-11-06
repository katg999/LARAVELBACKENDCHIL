<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\School;
use App\Models\HealthFacility;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Duration;
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
            'duration_id' => 'required|exists:durations,id',
            'appointment_time' => 'required|date',
            'reason' => 'required|string|max:500',
            'patient_id' => 'required|exists:patients,id',
            'school_id' => 'nullable|exists:schools,id',
            'health_facility_id' => 'nullable|exists:health_facilities,id'
        ]);

        // Additional validation
        $validator->after(function ($validator) use ($request) {
            // Parse the appointment time
            try {
                $appointmentDateTime = Carbon::parse($request->appointment_time);

                // Check if appointment is in the past
                if ($appointmentDateTime->isPast()) {
                    $validator->errors()->add('appointment_time', 'Cannot schedule appointments in the past.');
                }
            } catch (\Exception $e) {
                \Log::warning('Invalid appointment time format: ' . $request->appointment_time, ['error' => $e->getMessage()]);
                $validator->errors()->add('appointment_time', 'Invalid date or time format.');
                return;
            }

            // Check for time conflicts with existing appointments
            if ($request->filled('doctor_id') && $request->filled('appointment_time') && $request->filled('duration_id')) {
                try {
                    $appointmentDateTime = Carbon::parse($request->appointment_time);
                    $duration = Duration::find($request->duration_id);

                    if ($duration) {
                        $proposedStart = $appointmentDateTime;
                        $proposedEnd = $appointmentDateTime->copy()->addMinutes($duration->minutes);

                        // Get existing appointments for this doctor on the same date
                        $existingAppointments = Appointment::where('doctor_id', $request->doctor_id)
                            ->whereDate('appointment_time', $appointmentDateTime->toDateString())
                            ->where('status', '!=', 'cancelled')
                            ->get();

                        foreach ($existingAppointments as $existing) {
                            $existingStart = Carbon::parse($existing->appointment_time);
                            $existingDuration = $existing->duration ?? Duration::find($existing->duration_id);
                            $existingEnd = $existingStart->copy()->addMinutes($existingDuration ? $existingDuration->minutes : 30); // Default 30 mins if duration not found

                            // Check for overlap
                            if (($proposedStart->between($existingStart, $existingEnd) && !$proposedStart->equalTo($existingEnd)) ||
                                ($proposedEnd->between($existingStart, $existingEnd) && !$proposedEnd->equalTo($existingStart)) ||
                                ($proposedStart->lessThanOrEqualTo($existingStart) && $proposedEnd->greaterThan($existingStart))) {
                                $validator->errors()->add('appointment_time', 'This time slot conflicts with an existing appointment for this doctor.');
                                break;
                            }
                        }
                    } else {
                        \Log::warning('Duration not found: ' . $request->duration_id);
                        $validator->errors()->add('duration_id', 'Selected duration is not available.');
                    }
                } catch (\Exception $e) {
                    \Log::error('Error checking appointment conflicts: ' . $e->getMessage());
                    $validator->errors()->add('appointment_time', 'Unable to validate appointment time.');
                }
            }

            // Validate patient belongs to the institution
            if ($request->filled('patient_id')) {
                try {
                    $patient = Patient::find($request->patient_id);
                    if ($patient) {
                        if ($request->filled('health_facility_id') && $patient->health_facility_id != $request->health_facility_id) {
                            $validator->errors()->add('patient_id', 'Patient does not belong to this health facility');
                        }
                        if ($request->filled('school_id') && $patient->school_id != $request->school_id) {
                            $validator->errors()->add('patient_id', 'Patient does not belong to this school');
                        }
                    } else {
                        $validator->errors()->add('patient_id', 'Patient not found');
                    }
                } catch (\Exception $e) {
                    \Log::error('Error validating patient: ' . $e->getMessage());
                    $validator->errors()->add('patient_id', 'Unable to validate patient information');
                }
            }
        });

        if ($validator->fails()) {
            \Log::warning('Appointment validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        // Create appointment
        try {
            $appointmentDateTime = Carbon::parse($request->appointment_time);

            $appointment = Appointment::create([
                'doctor_id' => $request->doctor_id,
                'appointment_time' => $appointmentDateTime,
                'duration_id' => $request->duration_id,
                'reason' => $request->reason,
                'status' => 'awaiting_payment',
                'health_facility_id' => $request->health_facility_id,
                'patient_id' => $request->patient_id,
                'school_id' => $request->school_id
            ]);

            \Log::info('Appointment created successfully', [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'appointment_time' => $appointment->appointment_time
            ]);

            // Initiate payment if phone number is provided
            if ($request->filled('phone_number') && $request->filled('payment_method') && $request->payment_method === 'marzpay') {
                try {
                    $duration = $appointment->duration;
                    $amount = $duration->getPriceForDoctor($appointment->doctor);
                    
                    $marzPayService = new \App\Services\MarzPayService();
                    
                    // Normalize phone number (same as PaymentController)
                    $raw = $request->phone_number;
                    $digits = preg_replace('/\D/', '', $raw);
                    
                    if (strlen($digits) === 10 && str_starts_with($digits, '07')) {
                        // 07XXXXXXXX -> +2567XXXXXXXX
                        $phone = '+256' . substr($digits, 1);
                    } elseif (strlen($digits) === 12 && str_starts_with($digits, '2567')) {
                        // 2567XXXXXXXX -> +2567XXXXXXXX
                        $phone = '+' . $digits;
                    } elseif (strlen($digits) === 13 && str_starts_with($digits, '2560')) {
                        // 25607XXXXXXXX -> +2567XXXXXXXX
                        $phone = '+256' . substr($digits, 4);
                    } elseif (strlen($digits) === 9 && str_starts_with($digits, '7')) {
                        // 7XXXXXXXX -> +2567XXXXXXXX
                        $phone = '+256' . $digits;
                    } else {
                        $phone = '+' . $digits;
                    }
                    
                    $data = [
                        'amount' => $amount,
                        'phone_number' => $phone,
                        'country' => 'UG',
                        'reference' => (string) \Illuminate\Support\Str::uuid(),
                        'description' => 'Appointment payment - ' . $appointment->id,
                        'callback_url' => route('marzpay.webhook'),
                    ];
                    
                    \Log::info('Initiating MarzPay collection', $data);
                    
                    $result = $marzPayService->collectMoney($data);
                    
                    if (($result['status'] ?? null) === 'success') {
                        \Log::info('MarzPay collection initiated successfully', ['result' => $result]);
                        
                        // Create Payment record
                        $payment = \App\Models\Payment::create([
                            'appointment_id' => $appointment->id,
                            'amount' => $amount,
                            'phone_number' => $phone,
                            'reference_id' => $result['data']['transaction']['uuid'] ?? (string) \Illuminate\Support\Str::uuid(),
                            'status' => 'pending',
                            'metadata' => [
                                'marzpay_response' => $result,
                                'requested_at' => now(),
                            ]
                        ]);

                        // Store payment reference on appointment for tracking
                        $appointment->payment_reference = $payment->reference_id;
                        $appointment->payment_status = 'pending';
                        $appointment->save();

                        // Create initial Transaction record
                        \App\Models\Transaction::create([
                            'payment_id' => $payment->id,
                            'reference_id' => $payment->reference_id,
                            'amount' => $amount,
                            'status' => 'pending',
                            'transaction_id' => $result['data']['transaction']['uuid'] ?? null,
                            'provider' => 'marzpay',
                            'provider_reference' => $result['data']['transaction']['uuid'] ?? null,
                            'marzpay_uuid' => $result['data']['transaction']['uuid'] ?? null,
                            'country' => 'UG',
                            'description' => 'Appointment payment - ' . $appointment->id,
                            'transaction_type' => 'collection',
                            'webhook_event_type' => 'collection.pending',
                            'collection_data' => $result,
                        ]);
                        
                        $message = 'Payment request sent. Please approve on your phone.';
                        if ($request->ajax() || $request->wantsJson()) {
                            return response()->json([
                                'success' => true,
                                'message' => $message,
                                'status' => 'pending',
                                'reference_id' => $appointment->payment_reference,
                                'appointment' => $appointment->load(['patient', 'doctor'])
                            ]);
                        }
                    } else {
                        \Log::warning('MarzPay collection failed', ['result' => $result]);
                        
                        $message = $result['message'] ?? 'Failed to initiate payment';
                        if ($request->ajax() || $request->wantsJson()) {
                            return response()->json([
                                'success' => false,
                                'message' => $message
                            ], 422);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Error initiating payment', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage()
                    ]);
                    
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Error initiating payment: ' . $e->getMessage()
                        ], 500);
                    }
                }
            }

            // Check if this is an AJAX request (fallback if no payment)
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment scheduled successfully',
                    'appointment' => $appointment->load(['patient', 'doctor'])
                ]);
            }

            // Redirect based on context
            if ($appointment->health_facility_id) {
                return redirect()->route('health-facility.appointments', ['id' => $appointment->health_facility_id])
                    ->with('success', 'Appointment booked successfully');
            }
            if ($appointment->school_id) {
                return redirect()->route('book-doctor', ['school' => $appointment->school_id])
                    ->with('success', 'Appointment booked successfully');
            }
            return redirect()->back()->with('success', 'Appointment booked successfully');

        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Database error during appointment creation', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            $errorMessage = 'Database error occurred while creating appointment';
            if (str_contains($e->getMessage(), 'UNIQUE constraint')) {
                $errorMessage = 'This appointment time conflicts with an existing booking';
            } elseif (str_contains($e->getMessage(), 'FOREIGN KEY constraint')) {
                $errorMessage = 'Invalid doctor, patient, or duration selected';
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => $e->getMessage()
                ], 500);
            } else {
                return redirect()->back()->with('error', $errorMessage)->withInput();
            }
        } catch (\Exception $e) {
            \Log::error('Unexpected error during appointment creation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred while creating the appointment',
                    'error' => $e->getMessage()
                ], 500);
            } else {
                return redirect()->back()->with('error', 'An unexpected error occurred. Please try again.')->withInput();
            }
        }
    }

    /**
     * Validate appointment data before creation
     */
    public function validateAppointment(Request $request)
    {
        // Use the same validation logic as store method
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:doctors,id',
            'duration_id' => 'required|exists:durations,id',
            'appointment_time' => 'required|date',
            'reason' => 'nullable|string|max:500',
            'patient_id' => 'nullable|exists:patients,id',
            'school_id' => 'nullable|exists:schools,id',
            'health_facility_id' => 'nullable|exists:health_facilities,id'
        ]);

        // Additional validation (same as store method)
        $validator->after(function ($validator) use ($request) {
            try {
                $appointmentDateTime = Carbon::parse($request->appointment_time);
                if ($appointmentDateTime->isPast()) {
                    $validator->errors()->add('appointment_time', 'Cannot schedule appointments in the past.');
                }
            } catch (\Exception $e) {
                $validator->errors()->add('appointment_time', 'Invalid date or time format.');
                return;
            }

            if ($request->filled('doctor_id') && $request->filled('appointment_time') && $request->filled('duration_id')) {
                try {
                    $appointmentDateTime = Carbon::parse($request->appointment_time);
                    $duration = Duration::find($request->duration_id);

                    if ($duration) {
                        $proposedStart = $appointmentDateTime;
                        $proposedEnd = $appointmentDateTime->copy()->addMinutes($duration->minutes);

                        $existingAppointments = Appointment::where('doctor_id', $request->doctor_id)
                            ->whereDate('appointment_time', $appointmentDateTime->toDateString())
                            ->where('status', '!=', 'cancelled')
                            ->get();

                        foreach ($existingAppointments as $existing) {
                            $existingStart = Carbon::parse($existing->appointment_time);
                            $existingDuration = $existing->duration ?? Duration::find($existing->duration_id);
                            $existingEnd = $existingStart->copy()->addMinutes($existingDuration ? $existingDuration->minutes : 30);

                            if (($proposedStart->between($existingStart, $existingEnd) && !$proposedStart->equalTo($existingEnd)) ||
                                ($proposedEnd->between($existingStart, $existingEnd) && !$proposedEnd->equalTo($existingStart)) ||
                                ($proposedStart->lessThanOrEqualTo($existingStart) && $proposedEnd->greaterThan($existingStart))) {
                                $validator->errors()->add('appointment_time', 'This time slot conflicts with an existing appointment for this doctor.');
                                break;
                            }
                        }
                    } else {
                        $validator->errors()->add('duration_id', 'Selected duration is not available.');
                    }
                } catch (\Exception $e) {
                    $validator->errors()->add('appointment_time', 'Unable to validate appointment time.');
                }
            }

            if ($request->filled('patient_id')) {
                try {
                    $patient = Patient::find($request->patient_id);
                    if ($patient) {
                        if ($request->filled('health_facility_id') && $patient->health_facility_id != $request->health_facility_id) {
                            $validator->errors()->add('patient_id', 'Patient does not belong to this health facility');
                        }
                        if ($request->filled('school_id') && $patient->school_id != $request->school_id) {
                            $validator->errors()->add('patient_id', 'Patient does not belong to this school');
                        }
                    } else {
                        $validator->errors()->add('patient_id', 'Patient not found');
                    }
                } catch (\Exception $e) {
                    $validator->errors()->add('patient_id', 'Unable to validate patient information');
                }
            }
        });

        if ($validator->fails()) {
            // Check if it's a conflict error
            $conflictError = $validator->errors()->first('appointment_time');
            
            return response()->json([
                'valid' => false,
                'available' => false,
                'message' => $conflictError ?: 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'available' => true,
            'message' => 'Time slot is available'
        ]);
    }

    public function index(Request $request)
    {
        $query = Appointment::query();
        
        if ($request->has('school_id')) {
            $query->where('school_id', $request->school_id)
                  ->with(['patient', 'doctor']);
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
            ->with(['patient', 'doctor'])
            ->firstOrFail();

        return response()->json([
            'status' => $appointment->status,
            'appointment' => $appointment
        ]);
    }

    /**
     * Mark an appointment as cancelled
     */
    public function cancel(Request $request, Appointment $appointment)
    {
        // Check if appointment has been paid
        if ($appointment->payment_status === 'completed') {
            $message = 'Cannot cancel appointment that has already been paid.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $appointment->status = 'cancelled';
        $appointment->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => 'cancelled']);
        }

        return redirect()->back()->with('success', 'Appointment cancelled');
    }

        /**
     * Mark an appointment as completed (doctor marks as done, awaiting institution approval)
     */
    public function complete(Request $request, Appointment $appointment)
    {
        // If route model binding didn't work (e.g., in tests), find the appointment manually
        if (!$appointment->exists) {
            $appointmentId = $request->route('appointment');
            if (is_object($appointmentId)) {
                $appointmentId = $appointmentId->id;
            }
            $appointment = Appointment::findOrFail($appointmentId);
        }

        $appointment->status = 'awaiting_approval';
        $appointment->save();

        // Send notification to institution
        $this->sendApprovalNotification($appointment);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => 'awaiting_approval']);
        }

        return redirect()->back()->with('success', 'Appointment marked as completed, awaiting institution approval');
    }

    /**
     * Approve an appointment (institution approves completed appointment)
     */
    public function approve(Request $request, Appointment $appointment)
    {
        // If route model binding didn't work (e.g., in tests), find the appointment manually
        if (!$appointment->exists) {
            $appointmentId = $request->route('appointment');
            if (is_object($appointmentId)) {
                $appointmentId = $appointmentId->id;
            }
            $appointment = Appointment::findOrFail($appointmentId);
        }

        // Check if appointment is awaiting approval
        if ($appointment->status !== 'awaiting_approval') {
            $message = 'Only appointments awaiting approval can be approved.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $appointment->status = 'completed';
        $appointment->save();

        // Send notification to doctor
        $this->sendApprovalNotificationToDoctor($appointment);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => 'completed']);
        }

        return redirect()->back()->with('success', 'Appointment approved successfully');
    }

    protected function sendAppointmentConfirmation(Appointment $appointment)
    {
        $user = $appointment->patient;
        $institution = $appointment->school ?? $appointment->healthFacility;
        $doctor = $appointment->doctor;
        
        $message = "Appointment Confirmed:\n\n" .
                   "Patient: {$user->name}\n" .
                   "Doctor: Dr. {$doctor->name}\n" .
                   "Type: " . ($doctor->specialization === 'General Practitioner' ? 'General' : 'Specialist') . "\n" .
                   "Duration: {$appointment->duration->minutes} mins\n" .
                   "Time: {$appointment->appointment_time->format('D, M j, Y g:i A')}\n" .
                   "Reason: {$appointment->reason}";

        // Send to appropriate contacts
        if ($appointment->patient) {
            $contactNumber = $appointment->patient->contact_number ?? $appointment->patient->parent_contact;
            if ($contactNumber) {
                $this->sendSms($contactNumber, $message);
            }
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

    protected function sendApprovalNotification(Appointment $appointment)
    {
        $institution = $appointment->school ?? $appointment->healthFacility;

        if ($institution && $institution->email) {
            try {
                \Mail::to($institution->email)->send(new \App\Mail\AppointmentApprovalMail($appointment));
            } catch (\Exception $e) {
                \Log::error('Failed to send approval notification email', [
                    'appointment_id' => $appointment->id,
                    'institution_email' => $institution->email,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    protected function sendApprovalNotificationToDoctor(Appointment $appointment)
    {
        $doctor = $appointment->doctor;
        $institution = $appointment->school ?? $appointment->healthFacility;

        if ($doctor && $doctor->email) {
            try {
                \Mail::to($doctor->email)->send(new \App\Mail\AppointmentApprovedMail($appointment));
            } catch (\Exception $e) {
                \Log::error('Failed to send approval notification email to doctor', [
                    'appointment_id' => $appointment->id,
                    'doctor_email' => $doctor->email,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Delete an appointment (only if cancelled or awaiting_payment)
     */
    public function destroy(Request $request, $id)
    {
        try {
            $appointment = Appointment::findOrFail($id);

            // Only allow deletion of cancelled or awaiting_payment appointments
            if (!in_array($appointment->status, ['cancelled', 'awaiting_payment'])) {
                \Log::warning('Attempted to delete appointment with invalid status', [
                    'appointment_id' => $id,
                    'status' => $appointment->status
                ]);

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only cancelled or pending appointments can be deleted.'
                    ], 400);
                }

                return redirect()->back()->with('error', 'Only cancelled or pending appointments can be deleted.');
            }

            \Log::info('Deleting appointment', [
                'appointment_id' => $id,
                'status' => $appointment->status
            ]);

            $appointment->delete();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment deleted successfully.'
                ]);
            }

            return redirect()->back()->with('success', 'Appointment deleted successfully.');

        } catch (\Exception $e) {
            \Log::error('Failed to delete appointment', [
                'appointment_id' => $id,
                'error' => $e->getMessage()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete appointment: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to delete appointment.');
        }
    }
}