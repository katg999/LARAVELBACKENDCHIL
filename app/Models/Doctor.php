<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    protected $fillable = [
        'specialization_id',
        'name',
        'email',
        'phone',
        'availability',
        'is_available'
    ];

    protected $casts = [
        'availability' => 'array',
        'is_available' => 'boolean'
    ];

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function getScheduleAttribute(): string
    {
        return collect($this->availability)
            ->map(fn ($days, $time) => "$time: ".implode(', ', $days))
            ->join(' | ');
    }
}