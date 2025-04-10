<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name',
        'email',
        'contact',
        'file_url'
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function labRequests(): HasMany
    {
        return $this->hasMany(LabRequest::class);
    }

    public function getDashboardUrlAttribute(): string
    {
        return route('filament.school.pages.dashboard', ['school' => $this->id]);
    }
}