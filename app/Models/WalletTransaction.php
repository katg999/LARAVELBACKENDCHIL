<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_type',
        'user_id',
        'type',
        'amount',
        'balance_after',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /**
     * Get the user that owns the transaction
     */
    public function user()
    {
        return match($this->user_type) {
            'doctor' => $this->belongsTo(Doctor::class, 'user_id'),
            'school' => $this->belongsTo(School::class, 'user_id'),
            'health_facility' => $this->belongsTo(HealthFacility::class, 'user_id'),
            default => null,
        };
    }

    /**
     * Scope for specific user type
     */
    public function scopeForUserType($query, $userType)
    {
        return $query->where('user_type', $userType);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, $userType, $userId)
    {
        return $query->where('user_type', $userType)->where('user_id', $userId);
    }

    /**
     * Scope for deposits
     */
    public function scopeDeposits($query)
    {
        return $query->where('type', 'deposit');
    }

    /**
     * Scope for withdrawals
     */
    public function scopeWithdrawals($query)
    {
        return $query->where('type', 'withdraw');
    }
}
