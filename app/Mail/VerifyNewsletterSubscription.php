<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyNewsletterSubscription extends Mailable
{
    use Queueable, SerializesModels;

    public $subscriber;
    public $verificationUrl;

    public function __construct(NewsletterSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
        $this->verificationUrl = url("/verify-newsletter/{$subscriber->verification_token}");
    }

    public function build()
    {
        return $this->subject('Verify Your Newsletter Subscription')
                    ->markdown('emails.verify-newsletter');
    }
}
