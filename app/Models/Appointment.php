<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model {
    protected $fillable = [
        'school_id',
        'student_id',
        'doctor_id',
        'appointment_time',
        'reason',
        'status'
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
}