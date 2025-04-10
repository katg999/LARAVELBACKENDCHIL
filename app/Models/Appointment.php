<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'doctor_id',
        'appointment_time',
        'reason',
        'notes',
        'status',
        'meeting_url'
    ];

    protected $casts = [
        'appointment_time' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('school', function (Builder $builder) {
            if (auth()->check() && auth()->user()->school_id) {
                $builder->where('school_id', auth()->user()->school_id);
            }
        });
    }

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

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('appointment_time', '>=', now())
            ->where('status', 'approved');
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->appointment_time->format('D, M j, Y \a\t g:i A');
    }
}