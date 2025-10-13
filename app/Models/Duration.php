<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Duration extends Model
{
    protected $fillable = [
        'minutes',
        'general_price',
        'specialist_price',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'general_price' => 'decimal:2',
        'specialist_price' => 'decimal:2',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGeneral($query)
    {
        return $query->where('type', 'general');
    }

    public function scopeSpecialist($query)
    {
        return $query->where('type', 'specialist');
    }

    /**
     * Get the price for this duration based on its type
     */
    public function getPrice(): float
    {
        return $this->type === 'general' ? $this->general_price : $this->specialist_price;
    }
}
