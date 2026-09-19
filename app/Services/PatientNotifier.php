<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\URL;

/** Messages sent to patients. Uses SMS today; a WhatsApp sender can be added behind the same methods. */
class PatientNotifier
{
    public function __construct(private SmsService $sms)
    {
    }

    /** Text the patient a signed link to their visit page (the room only shows when joining is open). */
    public function sendJoinLink(Appointment $appointment): bool
    {
        $patient = $appointment->patient;
        $doctor = $appointment->doctor;
        $number = $patient->contact_number ?? $patient->parent_contact ?? null;

        if (!$patient || !$doctor || !$number) {
            return false;
        }

        $link = URL::temporarySignedRoute(
            'visit.show',
            $appointment->appointment_time->copy()->addDay(),
            ['appointment' => $appointment->id]
        );

        return $this->sms->send(
            $number,
            "Your appointment with Dr. {$doctor->name} is confirmed for "
            . $appointment->appointment_time->format('D j M, g:i A')
            . ". Join here when it is time: " . $link
        );
    }

    /** Tell the patient their insurer check was declined and they can pay another way. */
    public function sendInsuranceDeclined(Appointment $appointment): bool
    {
        $patient = $appointment->patient;
        $number = $patient->contact_number ?? $patient->parent_contact ?? null;
        if (!$number) {
            return false;
        }

        return $this->sms->send(
            $number,
            "We could not confirm your insurance cover for your appointment on "
            . $appointment->appointment_time->format('D j M, g:i A')
            . ". You can still pay by mobile money to keep the booking."
        );
    }
}
