<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\URL;

/** What we tell patients and when. Delivery (SMS or WhatsApp) is PatientMessenger's job. */
class PatientNotifier
{
    public function __construct(private PatientMessenger $messenger)
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

        return $this->messenger->send(
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

        return $this->messenger->send(
            $number,
            "We could not confirm your insurance cover for your appointment on "
            . $appointment->appointment_time->format('D j M, g:i A')
            . ". You can still pay by mobile money to keep the booking."
        );
    }

    public function sendPrescriptionReviewed(\App\Models\Prescription $prescription): bool
    {
        $text = $prescription->status === 'approved'
            ? 'Your prescription was approved. You can now request delivery from your visits page.'
            : 'We could not accept your prescription photo.' . ($prescription->review_note ? ' Reason: ' . $prescription->review_note : '');

        return $this->toPatient($prescription->patient, $text);
    }

    public function sendDeliveryUpdate(\App\Models\Prescription $prescription): bool
    {
        $text = match ($prescription->delivery_status) {
            'preparing' => 'Your medicine order is being prepared.',
            'out_for_delivery' => 'Your medicine is on its way.',
            'delivered' => 'Your medicine was delivered. Get well soon.',
            'cancelled' => 'Your medicine delivery was cancelled. Contact the clinic if this is a surprise.',
            default => null,
        };

        return $text ? $this->toPatient($prescription->patient, $text) : false;
    }

    private function toPatient(?\App\Models\Patient $patient, string $message): bool
    {
        $number = $patient->contact_number ?? $patient->parent_contact ?? null;

        return $number ? $this->messenger->send($number, $message) : false;
    }
}
