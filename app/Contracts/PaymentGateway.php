<?php

namespace App\Contracts;

/**
 * What the appointment checkout needs from a payment provider.
 * MarzPay (mobile money) implements it today; a card gateway such as
 * Pegasus can implement it later without touching the checkout code.
 */
interface PaymentGateway
{
    /** Short provider key stored on transactions, e.g. "marzpay". */
    public function name(): string;

    /**
     * Ask the customer to pay.
     *
     * @param  array $data amount, phone_number, country, reference, description, callback_url
     * @return array provider response; ['status' => 'success', 'data' => ['transaction' => ['uuid' => ...]]]
     */
    public function collect(array $data): array;

    /** Look up a payment by the provider's reference. */
    public function status(string $reference): array;
}
