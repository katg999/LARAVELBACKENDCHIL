<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyNewsletterSubscription extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subscriber;
    public $verificationUrl;

    public function __construct(NewsletterSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
        $this->verificationUrl = route('newsletter.verify', ['token' => $subscriber->verification_token]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Keti AI Newsletter Subscription',
            metadata: [
                'marketing' => 'newsletter',
            ]
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.verify-newsletter',
            with: [
                'verificationUrl' => $this->verificationUrl,
                'appName' => config('app.name')
            ]
        );
    }

    public function build()
    {
        return $this->withSwiftMessage(function ($message) {
            $headers = $message->getHeaders();
            $headers->addTextHeader('List-Unsubscribe', '<mailto:unsubscribe@yourdomain.com>');
        });
    }
}