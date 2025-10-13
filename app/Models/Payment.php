<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'appointment_id',
        'amount',
        'phone_number',
        'reference_id',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2',
    ];

    /**
     * Validation rules for the Payment model
     */
    public static function rules($id = null)
    {
        return [
            'appointment_id' => 'required|exists:appointments,id',
            'amount' => 'required|numeric|min:0',
            'phone_number' => 'required|string|max:20',
            'reference_id' => 'required|string|unique:payments,reference_id' . ($id ? ',' . $id : ''),
            'status' => 'in:pending,completed,failed,cancelled',
            'metadata' => 'nullable|array',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
