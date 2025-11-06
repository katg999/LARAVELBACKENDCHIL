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

class AppointmentApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;
    public $doctor;
    public $patient;
    public $institution;

    /**
     * Create a new message instance.
     */
    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
        $this->doctor = $appointment->doctor;
        $this->patient = $appointment->patient;
        $this->institution = $appointment->school ?? $appointment->healthFacility;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Appointment Completed - Awaiting Your Approval',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-approval',
            with: [
                'appointment' => $this->appointment,
                'doctor' => $this->doctor,
                'patient' => $this->patient,
                'institution' => $this->institution,
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
}