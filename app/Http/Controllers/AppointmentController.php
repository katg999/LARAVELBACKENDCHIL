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

            // Send confirmation if needed
            // $this->sendAppointmentConfirmation($appointment); // Removed - confirmation now requires payment

            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment scheduled successfully',
                    'appointment' => $appointment->load(['patient', 'doctor'])
                ]);
            }

            // Redirect based on context
            if ($appointment->health_facility_id) {
                return redirect()->route('health-facility.book-doctor', ['id' => $appointment->health_facility_id])
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
            'reason' => 'required|string|max:500',
            'patient_id' => 'required|exists:patients,id',
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
            return response()->json([
                'valid' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Appointment data is valid'
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
}