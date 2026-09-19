<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends SMS through Africa's Talking when configured, and only logs otherwise
 * (local development, or before credentials are added).
 */
class SmsService
{
    public function send(?string $number, string $message): bool
    {
        $to = $this->normalizeUgandanNumber($number);
        if (!$to) {
            Log::warning('SMS skipped: no valid phone number', ['number' => $number]);
            return false;
        }

        $username = config('services.africastalking.username');
        $apiKey = config('services.africastalking.api_key');

        if (!$username || !$apiKey) {
            Log::info('SMS (not sent, provider not configured)', ['to' => $to, 'message' => $message]);
            return false;
        }

        try {
            $response = Http::withHeaders(['apiKey' => $apiKey, 'Accept' => 'application/json'])
                ->asForm()
                ->post(config('services.africastalking.url'), array_filter([
                    'username' => $username,
                    'to' => $to,
                    'message' => $message,
                    'from' => config('services.africastalking.sender_id'),
                ]));

            if (!$response->successful()) {
                Log::error('SMS provider error', ['status' => $response->status(), 'to' => $to]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('SMS send failed: ' . $e->getMessage(), ['to' => $to]);
            return false;
        }
    }

    /** 0772123456 / 256772123456 / +256772123456 -> +256772123456 */
    public function normalizeUgandanNumber(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if (str_starts_with($digits, '256') && strlen($digits) === 12) {
            return '+' . $digits;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+256' . substr($digits, 1);
        }
        if (strlen($digits) === 9) {
            return '+256' . $digits;
        }

        return null;
    }
}
