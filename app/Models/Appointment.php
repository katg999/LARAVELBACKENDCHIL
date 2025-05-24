<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model {
    protected $fillable = [
        'school_id',
        'student_id',
        'doctor_id',
        'duration',
        'appointment_time',
        'reason',
        'patient_id',
        'health_facility_id',
        'status',
        'school_id',       // nullable
       'health_facility_id', // nullable
       'patient_id',      // nullable
       'student_id'       // nullable
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
    
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
    
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function healthFacility()
{
    return $this->belongsTo(HealthFacility::class);

}

    

  
    
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }


    // Helper method to get the institution (school or health facility)
    public function institution()
    {
        return $this->school_id ? $this->school : $this->healthFacility;
    }

    // Helper method to get the user (student or patient)
    public function user()
    {
        return $this->student_id ? $this->student : $this->patient;
    }
}