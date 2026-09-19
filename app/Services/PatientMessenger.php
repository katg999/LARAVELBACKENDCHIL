<?php

namespace App\Services;

/**
 * One place to message a patient. NOTIFY_CHANNEL chooses: sms (default), whatsapp
 * (falls back to SMS when WhatsApp refuses or is not set up), or both.
 * One-time login codes always go by SMS, not through here.
 */
class PatientMessenger
{
    public function __construct(private SmsService $sms, private WhatsAppSender $whatsapp)
    {
    }

    public function send(?string $number, string $message): bool
    {
        $channel = config('services.notifications.channel', 'sms');

        if ($channel === 'both') {
            $wa = $this->whatsapp->send($number, $message);
            $sms = $this->sms->send($number, $message);

            return $wa || $sms;
        }

        if ($channel === 'whatsapp' && $this->whatsapp->send($number, $message)) {
            return true;
        }

        return $this->sms->send($number, $message);
    }
}
