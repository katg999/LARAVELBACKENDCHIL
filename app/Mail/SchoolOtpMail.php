<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SchoolOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;

    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your School Login OTP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: ['otp' => $this->otp]
        );
    }

    public function attachments(): array
    {
        return [];
    }

    // Additional build method for better Gmail compatibility
    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                   ->view('emails.otp')
                   ->with(['otp' => $this->otp]);
    }
}