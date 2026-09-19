<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model {
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment) {
            if (empty($appointment->meeting_room)) {
                $appointment->meeting_room = static::newMeetingRoom();
            }
        });
    }

    public static function newMeetingRoom(): string
    {
        return 'easemed-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(24));
    }

    /**
     * Private video room URL for this appointment only.
     */
    public function getMeetingUrlAttribute(): string
    {
        if (empty($this->meeting_room)) {
            $this->meeting_room = static::newMeetingRoom();
            $this->saveQuietly();
        }

        return rtrim(config('services.jitsi.base_url', 'https://meet.jit.si'), '/') . '/' . $this->meeting_room;
    }
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
        'meeting_room',
        'coverage_type',
        'member_policy_id',
        'employer_id',
        'payment_method',
        'visit_code',
        'insurance_status',
        'insurance_note',
        'claim_status', 'claim_reference', 'claim_note', 'claim_paid_amount', 'claim_submitted_at', 'claim_updated_at',
        // 'amount', // Removed - amount now comes from duration relationship
    ];
    

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'claim_submitted_at' => 'datetime',
        'claim_updated_at' => 'datetime',
        'claim_paid_amount' => 'decimal:2',
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

    public function consents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Consent::class);
    }

    /**
     * Visits an insurer can be asked to pay for: not cancelled, and the patient actually attended
     * (joined the video room, or the doctor completed it).
     */
    public function scopeAttended($query)
    {
        return $query->where('status', '!=', 'cancelled')->where(function ($q) {
            $q->whereIn('status', ['completed', 'awaiting_approval'])->orWhereHas('consents');
        });
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function memberPolicy(): BelongsTo
    {
        return $this->belongsTo(MemberPolicy::class);
    }

    public function duration(): BelongsTo
    {
        return $this->belongsTo(Duration::class);
    }

    public function healthFacility(): BelongsTo
    {
        return $this->belongsTo(HealthFacility::class);
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