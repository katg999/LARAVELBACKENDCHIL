<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $userType;
    public int $expiryMinutes;

    public function __construct(string $otp, string $userType = 'school', int $expiryMinutes = 10)
    {
        $this->otp = $otp;
        $this->userType = $userType;
        $this->expiryMinutes = $expiryMinutes;
    }

    public function envelope()
    {
        $subject = match ($this->userType) {
            'health_facility' => 'Your Health Facility Login OTP',
            'doctor' => 'Your Doctor Login OTP',
            default => 'Your School Login OTP',
        };

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
                'expiryMinutes' => $this->expiryMinutes,
            ],
        );
    }

    public function build()
    {
        $subject = match ($this->userType) {
            'health_facility' => 'Your Health Facility Login OTP',
            'doctor' => 'Your Doctor Login OTP',
            default => 'Your School Login OTP',
        };

        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->view('emails.otp')
            ->with([
                'otp' => $this->otp,
                'userType' => $this->userType,
                'expiryMinutes' => $this->expiryMinutes,
            ])
            ->subject($subject);
    }
}
