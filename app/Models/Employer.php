<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employer extends Model
{
    protected $fillable = ['name', 'contact_phone', 'monthly_cap', 'active'];

    protected $casts = ['monthly_cap' => 'decimal:2', 'active' => 'boolean'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'employer_members')->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
