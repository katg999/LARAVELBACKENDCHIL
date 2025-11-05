<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Authenticatable
{
    use HasFactory;
    protected $fillable = [
        'school_id',
        'name',
        'file_url',
        'specialization',
        'email', 
        'health_facility_id',
        'contact',
        'meeting_slug'
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function healthFacility(): BelongsTo
    {
        return $this->belongsTo(HealthFacility::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(DoctorAvailability::class);
    }

    public function medicalHistories(): HasMany
    {
        return $this->hasMany(MedicalHistory::class);
    }

    /**
     * Scope to get doctors available on a specific day
     */
    public function scopeAvailableOnDay($query, $dayOfWeek)
    {
        return $query->whereHas('availabilities', function ($q) use ($dayOfWeek) {
            $q->where('day', strtolower($dayOfWeek))
              ->where('available', true);
        });
    }

    /**
     * Check if doctor is available on a specific day
     */
    public function isAvailableOnDay($dayOfWeek)
    {
        return $this->availabilities()
            ->where('day', strtolower($dayOfWeek))
            ->where('available', true)
            ->exists();
    }
}
