<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\HealthFacilityInvitation;

class HealthFacilityInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invitation;
    public $invitationUrl;
    public $healthFacility;

    /**
     * Create a new message instance.
     */
    public function __construct(HealthFacilityInvitation $invitation, string $invitationUrl)
    {
        $this->invitation = $invitation;
        $this->invitationUrl = $invitationUrl;
        $this->healthFacility = $invitation->healthFacility;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation to Join ' . $this->healthFacility->name . ' - KETI AI',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.health-facility-invitation',
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
