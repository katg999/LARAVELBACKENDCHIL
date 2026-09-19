<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\School;
use App\Models\HealthFacility;

class AppointmentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;
    public $doctor;
    public $patient;
    public $institution;
    public $meetingLink;

    /**
     * Create a new message instance.
     */
    public function __construct(Appointment $appointment, Doctor $doctor, Patient $patient, $institution)
    {
        $this->appointment = $appointment;
        $this->doctor = $doctor;
        $this->patient = $patient;
        $this->institution = $institution;
        $this->meetingLink = $this->generateMeetingLink();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Appointment Confirmed - ' . $this->patient->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-confirmation',
            with: [
                'appointment' => $this->appointment,
                'doctor' => $this->doctor,
                'patient' => $this->patient,
                'institution' => $this->institution,
                'meetingLink' => $this->generateMeetingLink(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Generate meeting link for the doctor
     */
    protected function generateMeetingLink()
    {
        // One private room per appointment, not one shared room per doctor.
        return $this->appointment->meeting_url;
    }
}