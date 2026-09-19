<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends WhatsApp messages through Meta's WhatsApp Cloud API.
 *
 * Meta only lets a business send free-text messages inside 24 hours of the
 * customer's last message. Outside that window the send is refused, this returns
 * false, and PatientMessenger falls back to SMS.
 */
class WhatsAppSender
{
    public function configured(): bool
    {
        return (bool) (config('services.whatsapp.token') && config('services.whatsapp.phone_number_id'));
    }

    public function send(?string $number, string $message): bool
    {
        $to = preg_replace('/\D+/', '', (string) app(SmsService::class)->normalizeUgandanNumber($number));

        if (!$this->configured() || !$to) {
            return false;
        }

        try {
            $response = Http::withToken(config('services.whatsapp.token'))
                ->post(rtrim(config('services.whatsapp.url'), '/') . '/' . config('services.whatsapp.phone_number_id') . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

            if (!$response->successful()) {
                Log::warning('WhatsApp send refused', ['status' => $response->status(), 'to' => $to]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('WhatsApp send failed: ' . $e->getMessage());

            return false;
        }
    }
}
