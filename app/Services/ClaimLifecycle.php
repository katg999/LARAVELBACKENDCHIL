<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * The life of a claim after the file goes to the insurer. We record what the insurer tells us;
 * we never approve or pay a claim ourselves.
 *
 *   submitted -> accepted | queried | rejected | paid
 *   queried   -> submitted (corrected and sent again) | accepted | rejected
 *   rejected  -> submitted (appealed or corrected)
 *   accepted  -> paid
 *   paid      -> (closed)
 */
class ClaimLifecycle
{
    public const FLOW = [
        'submitted' => ['accepted', 'queried', 'rejected', 'paid'],
        'queried' => ['submitted', 'accepted', 'rejected'],
        'rejected' => ['submitted'],
        'accepted' => ['paid'],
        'paid' => [],
    ];

    public const OPEN = ['submitted', 'queried', 'accepted'];     // still waiting on the insurer

    /** Put a claim into the submitted state (first time, or again after a query or rejection). */
    public function submit(Model $claim): void
    {
        $claim->forceFill([
            'insurance_status' => 'submitted',
            'claim_status' => 'submitted',
            'claim_submitted_at' => $claim->claim_submitted_at ?? now(),
            'claim_updated_at' => now(),
        ])->save();
    }

    /**
     * Record the insurer's reply.
     *
     * @param array{reference?: ?string, note?: ?string, amount_paid?: float|int|string|null} $data
     * @throws \DomainException when the move is not allowed or the reply is missing what it needs
     */
    public function respond(Model $claim, string $to, array $data = []): Model
    {
        $from = $claim->claim_status;

        if ($from === null) {
            throw new \DomainException('This claim has not been submitted to the insurer yet.');
        }
        if (!in_array($to, self::FLOW[$from] ?? [], true)) {
            throw new \DomainException("A claim that is '{$from}' cannot move to '{$to}'.");
        }
        if (in_array($to, ['queried', 'rejected'], true) && blank($data['note'] ?? null)) {
            throw new \DomainException('Record the insurer\'s reason or question.');
        }
        if ($to === 'paid' && (!isset($data['amount_paid']) || !is_numeric($data['amount_paid']) || (float) $data['amount_paid'] < 0)) {
            throw new \DomainException('Record the amount the insurer paid.');
        }

        if ($to === 'submitted') {
            $this->submit($claim);
            $claim->forceFill(['claim_note' => $data['note'] ?? $claim->claim_note])->save();

            return $claim->fresh();
        }

        $claim->forceFill([
            'claim_status' => $to,
            'claim_reference' => $data['reference'] ?? $claim->claim_reference,
            'claim_note' => $data['note'] ?? $claim->claim_note,
            'claim_paid_amount' => $to === 'paid' ? (float) $data['amount_paid'] : $claim->claim_paid_amount,
            'claim_updated_at' => now(),
        ])->save();

        return $claim->fresh();
    }
}
