<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'file_url',
        'specialization',
        'email', 
        'health_facility_id',
        'contact'
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function availabilities(): HasMany
{
    return $this->hasMany(DoctorAvailability::class);
}

}