<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabRequest extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'lab_test_id',
        'notes',
        'status',
        'results'
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

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'orange',
            'processing' => 'blue',
            'completed' => 'green',
            default => 'gray'
        };
    }
}