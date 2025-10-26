<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model {
    use HasFactory;
    protected $fillable = [
        'school_id',
        'patient_id', // Unified patient reference (replaces student_id)
        'doctor_id',
        'duration_id',
        'appointment_time',
        'reason',
        'health_facility_id',
        'status',
        'payment_reference',
        'payment_status',
        // 'amount', // Removed - amount now comes from duration relationship
    ];
    

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'appointment_time' => 'datetime',
    ];
    
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function duration(): BelongsTo
    {
        return $this->belongsTo(Duration::class);
    }

    public function healthFacility(): BelongsTo
    {
        return $this->belongsTo(HealthFacility::class);
    }

    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class);
    }


    // Helper method to get the institution (school or health facility)
    public function institution()
    {
        return $this->school ?? $this->healthFacility;
    }

    // Helper method to get the patient (unified)
    public function user()
    {
        return $this->patient;
    }

    // Check if this is a school appointment
    public function isSchoolAppointment()
    {
        return !is_null($this->school_id);
    }

    // Check if this is a health facility appointment
    public function isHealthFacilityAppointment()
    {
        return !is_null($this->health_facility_id);
    }
}