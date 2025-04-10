<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $fillable = [
        'school_id',
        'student_number',
        'name',
        'email',
        'class',
        'date_of_birth',
        'medical_notes'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('school', function (Builder $builder) {
            if (auth()->check() && auth()->user()->school_id) {
                $builder->where('school_id', auth()->user()->school_id);
            }
        });

        // Generate student number if not provided
        static::creating(function ($student) {
            if (empty($student->student_number)) {
                $student->student_number = static::generateStudentNumber();
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function labRequests(): HasMany
    {
        return $this->hasMany(LabRequest::class);
    }

    protected static function generateStudentNumber(): string
    {
        $year = now()->format('Y');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        return "STU-{$year}-{$random}";
    }
}