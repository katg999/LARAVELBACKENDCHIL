<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\SchoolInvitation;

class SchoolInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invitation;
    public $invitationUrl;
    public $school;

    /**
     * Create a new message instance.
     */
    public function __construct(SchoolInvitation $invitation, string $invitationUrl)
    {
        $this->invitation = $invitation;
        $this->invitationUrl = $invitationUrl;
        $this->school = $invitation->school;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation to Join ' . $this->school->name . ' - KETI AI',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.school-invitation',
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
