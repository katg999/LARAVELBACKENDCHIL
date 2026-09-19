<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    protected $fillable = ['patient_id', 'balance'];

    protected $casts = ['balance' => 'decimal:2'];

    public static function forPatient(Patient $patient): self
    {
        return static::firstOrCreate(['patient_id' => $patient->id], ['balance' => 0]);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest();
    }

    public function credit(float $amount, ?string $note = null, ?int $appointmentId = null): WalletTransaction
    {
        return $this->move('credit', $amount, $note, $appointmentId);
    }

    /** @throws \RuntimeException when the balance does not cover the amount */
    public function debit(float $amount, ?string $note = null, ?int $appointmentId = null): WalletTransaction
    {
        return $this->move('debit', $amount, $note, $appointmentId);
    }

    /** Changes the balance under a row lock so two payments cannot spend the same money. */
    private function move(string $type, float $amount, ?string $note, ?int $appointmentId): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }

        return DB::transaction(function () use ($type, $amount, $note, $appointmentId) {
            $locked = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            $new = $type === 'credit' ? (float) $locked->balance + $amount : (float) $locked->balance - $amount;

            if ($new < 0) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            $locked->update(['balance' => $new]);
            $this->balance = $new;

            return $locked->transactions()->create([
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $new,
                'appointment_id' => $appointmentId,
                'note' => $note,
            ]);
        });
    }
}
