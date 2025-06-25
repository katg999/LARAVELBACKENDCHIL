<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class SchoolOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $userType;

    public function __construct(string $otp, string $userType = 'school')
    {
        $this->otp = $otp;
        $this->userType = $userType;
    }

    public function envelope()
    {
        $subject = $this->userType === 'health_facility'
            ? 'Your Health Facility Login OTP'
            : 'Your School Login OTP';

        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: $subject,
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.otp',
            with: [
                'otp' => $this->otp,
                'userType' => $this->userType,
            ],
        );
    }

    public function build()
    {
        $subject = $this->userType === 'health_facility'
            ? 'Your Health Facility Login OTP'
            : 'Your School Login OTP';

        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->view('emails.otp')
                    ->with([
                        'otp' => $this->otp,
                        'userType' => $this->userType,
                    ])
                    ->subject($subject);
    }
}
