<?php

namespace App\Services;

use App\Mail\DoctorAppointmentConfirmationMail;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Emails to doctors. One place so every way of confirming a visit (payment, insurance, wallet, employer) tells the doctor. */
class DoctorNotifier
{
    public function appointmentConfirmed(Appointment $appointment): bool
    {
        $doctor = $appointment->doctor;
        if (!$doctor || !$doctor->email) {
            return false;
        }

        try {
            Mail::to($doctor->email)->send(new DoctorAppointmentConfirmationMail($appointment));

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send doctor confirmation email: ' . $e->getMessage(), ['appointment_id' => $appointment->id]);

            return false;
        }
    }
}
