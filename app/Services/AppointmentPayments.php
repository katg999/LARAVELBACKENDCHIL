<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Starts a mobile money payment for an appointment and records it.
 * Shared by the web checkout, the patient's visit page and USSD.
 */
class AppointmentPayments
{
    public function __construct(private PaymentGateway $gateway)
    {
    }

    /** Amount due for an appointment, before any override. */
    public function amountFor(Appointment $appointment): float
    {
        $appointment->loadMissing(['duration', 'doctor']);

        return (float) ($appointment->duration
            ? $appointment->duration->getPriceForDoctor($appointment->doctor)
            : 1.00);
    }

    /** Normalise a Ugandan number to +256XXXXXXXXX. */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '07')) {
            return '+256' . substr($digits, 1);
        }
        if (str_starts_with($digits, '2560')) {
            return '+256' . substr($digits, 3);
        }
        if (str_starts_with($digits, '0') && strlen($digits) >= 9) {
            return '+256' . substr($digits, 1);
        }
        if (str_starts_with($digits, '256')) {
            return '+' . $digits;
        }
        if (strlen($digits) === 9 && $digits[0] === '7') {
            return '+256' . $digits;
        }

        return '+' . $digits;
    }

    /**
     * @return array{success: bool, message: string, status?: string, reference_id?: ?string}
     */
    public function requestMobileMoney(Appointment $appointment, string $phone, ?float $amount = null): array
    {
        $phone = $this->normalizePhone($phone);
        $amount ??= $this->amountFor($appointment);

        try {
            $result = $this->gateway->collect([
                'amount' => $amount,
                'phone_number' => $phone,
                'country' => 'UG',
                'reference' => (string) Str::uuid(),
                'description' => 'Appointment payment - ' . $appointment->id,
                'callback_url' => route('marzpay.webhook'),
            ]);

            if (($result['status'] ?? null) !== 'success') {
                return ['success' => false, 'message' => $result['message'] ?? 'Failed to initiate payment'];
            }

            $uuid = $result['data']['transaction']['uuid'] ?? null;

            $payment = Payment::create([
                'appointment_id' => $appointment->id,
                'amount' => $amount,
                'phone_number' => $phone,
                'reference_id' => $uuid ?? (string) Str::uuid(),
                'status' => 'pending',
                'metadata' => ['marzpay_response' => $result, 'requested_at' => now()],
            ]);

            $appointment->payment_reference = $payment->reference_id;
            $appointment->payment_status = 'pending';
            $appointment->payment_method = 'mobile_money';
            $appointment->save();

            Transaction::create([
                'payment_id' => $payment->id,
                'reference_id' => $payment->reference_id,
                'amount' => $amount,
                'status' => 'pending',
                'transaction_id' => $uuid,
                'provider' => $this->gateway->name(),
                'provider_reference' => $uuid,
                'marzpay_uuid' => $uuid,
                'country' => 'UG',
                'description' => 'Appointment payment - ' . $appointment->id,
                'transaction_type' => 'collection',
                'webhook_event_type' => 'collection.pending',
                'collection_data' => $result,
            ]);

            return [
                'success' => true,
                'message' => 'Payment request sent. Please approve on your phone.',
                'status' => 'pending',
                'reference_id' => $appointment->payment_reference,
            ];
        } catch (\Exception $e) {
            Log::error('Appointment Checkout Error: ' . $e->getMessage());

            return ['success' => false, 'message' => 'An error occurred while processing payment'];
        }
    }
}
