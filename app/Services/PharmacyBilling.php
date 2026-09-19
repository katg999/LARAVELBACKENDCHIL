<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\MemberPolicy;
use App\Models\Prescription;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Money side of medicine: price a prescription, split it between the insurer and the patient,
 * and take the patient's share. Delivery only opens once the patient's share is paid.
 */
class PharmacyBilling
{
    public function __construct(private PaymentGateway $gateway, private AppointmentPayments $phones)
    {
    }

    /**
     * @param array<int, float|int|string> $prices item id => unit price (every item needs one)
     * @throws \DomainException when the prescription cannot be priced in its current state
     */
    public function price(Prescription $rx, array $prices, string $coverage, ?int $policyId = null): Prescription
    {
        if (!in_array($rx->status, ['issued', 'approved'], true)) {
            throw new \DomainException('Only an issued or approved prescription can be priced.');
        }
        if ($rx->delivery_status !== null || in_array($rx->payment_status, ['pending', 'paid'], true)) {
            throw new \DomainException('This prescription has already been paid for or sent for delivery.');
        }

        $rx->loadMissing('items');
        $total = 0.0;
        foreach ($rx->items as $item) {
            if (!isset($prices[$item->id]) || !is_numeric($prices[$item->id]) || (float) $prices[$item->id] < 0) {
                throw new \DomainException("Every item needs a price ('{$item->name}' has none).");
            }
        }

        DB::transaction(function () use ($rx, $prices, $coverage, $policyId, &$total) {
            foreach ($rx->items as $item) {
                $item->update(['unit_price' => (float) $prices[$item->id]]);
                $total += (float) $prices[$item->id] * max(1, (int) $item->quantity);
            }

            $base = ['total_amount' => $total, 'priced_at' => now(), 'insurer_reference' => null, 'insurance_note' => null, 'payment_method' => null, 'payment_reference' => null];

            if ($coverage === 'insurance') {
                $policy = $policyId ? MemberPolicy::find($policyId) : null;
                if (!$policy || (int) $policy->patient_id !== (int) $rx->patient_id || $policy->status !== 'active') {
                    throw new \DomainException('That insurance policy is not active for this patient.');
                }
                $rx->update($base + [
                    'coverage_type' => 'insurance', 'member_policy_id' => $policy->id, 'insurance_status' => 'pending',
                    'insurer_amount' => null, 'patient_amount' => null, 'payment_status' => null,
                ]);
            } else {
                $rx->update($base + [
                    'coverage_type' => 'self_pay', 'member_policy_id' => null, 'insurance_status' => null,
                    'insurer_amount' => 0, 'patient_amount' => $total, 'payment_status' => $total > 0 ? 'unpaid' : 'paid',
                ]);
            }
        });

        return $rx->fresh(['items']);
    }

    /** Record what the insurer agreed to pay (or that it declined), which fixes the patient's share. */
    public function decideInsurance(Prescription $rx, string $result, ?float $insurerAmount, ?string $reference, ?string $note): Prescription
    {
        if ($rx->coverage_type !== 'insurance' || $rx->insurance_status !== 'pending') {
            throw new \DomainException('This prescription is not waiting for an insurer decision.');
        }

        $total = (float) $rx->total_amount;

        if ($result === 'approved') {
            if ($insurerAmount === null || $insurerAmount < 0 || $insurerAmount > $total) {
                throw new \DomainException('The insurer amount must be between 0 and the total.');
            }
            $patient = round($total - $insurerAmount, 2);
            $rx->update([
                'insurance_status' => 'approved', 'insurer_amount' => $insurerAmount, 'patient_amount' => $patient,
                'insurer_reference' => $reference, 'insurance_note' => $note,
                'payment_status' => $patient > 0 ? 'unpaid' : 'paid', 'payment_method' => $patient > 0 ? null : 'insurance',
            ]);
        } else {
            $rx->update([
                'insurance_status' => 'declined', 'coverage_type' => 'self_pay', 'insurer_amount' => 0,
                'patient_amount' => $total, 'insurance_note' => $note, 'payment_status' => $total > 0 ? 'unpaid' : 'paid',
            ]);
        }

        return $rx->fresh(['items']);
    }

    /** Ask for the patient's share by mobile money. @return array{success: bool, message: string} */
    public function requestMobileMoney(Prescription $rx, string $phone): array
    {
        if ($rx->payment_status !== 'unpaid' || $rx->amountDue() === null) {
            return ['success' => false, 'message' => 'There is nothing to pay for this prescription right now.'];
        }

        try {
            $result = $this->gateway->collect([
                'amount' => $rx->amountDue(),
                'phone_number' => $this->phones->normalizePhone($phone),
                'country' => 'UG',
                'reference' => (string) Str::uuid(),
                'description' => 'Medicine - prescription ' . $rx->id,
                'callback_url' => route('marzpay.webhook'),
            ]);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'An error occurred while processing payment'];
        }

        if (($result['status'] ?? null) !== 'success') {
            return ['success' => false, 'message' => $result['message'] ?? 'Failed to initiate payment'];
        }

        $rx->update([
            'payment_status' => 'pending', 'payment_method' => 'mobile_money',
            'payment_reference' => $result['data']['transaction']['uuid'] ?? null,
        ]);

        return ['success' => true, 'message' => 'Payment request sent. Please approve on your phone.'];
    }

    /** Pay the patient's share from their wallet. @throws \RuntimeException when the balance is too low */
    public function payFromWallet(Prescription $rx): void
    {
        if ($rx->payment_status !== 'unpaid' || $rx->amountDue() === null) {
            throw new \DomainException('There is nothing to pay for this prescription right now.');
        }

        DB::transaction(function () use ($rx) {
            Wallet::forPatient($rx->patient)->debit($rx->amountDue(), 'Medicine - prescription ' . $rx->id, null);
            $rx->update(['payment_status' => 'paid', 'payment_method' => 'wallet']);
        });
    }

    /** Called when the payment provider confirms the patient's share. */
    public function markPaid(Prescription $rx): void
    {
        if ($rx->payment_status !== 'paid') {
            $rx->update(['payment_status' => 'paid', 'payment_method' => $rx->payment_method ?? 'mobile_money']);
        }
    }

    /** Called when the provider reports the request failed or was cancelled, so the patient can try again. */
    public function markPaymentFailed(Prescription $rx): void
    {
        if ($rx->payment_status === 'pending') {
            $rx->update(['payment_status' => 'unpaid', 'payment_reference' => null]);
        }
    }
}
