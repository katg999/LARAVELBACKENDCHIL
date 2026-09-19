<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = ['wallet_id', 'type', 'amount', 'balance_after', 'appointment_id', 'note'];

    protected $casts = ['amount' => 'decimal:2', 'balance_after' => 'decimal:2'];
}
