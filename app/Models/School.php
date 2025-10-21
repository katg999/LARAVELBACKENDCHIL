<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class School extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'contact', 'file_url', 'wallet_balance'];
    
    protected $casts = [
        'wallet_balance' => 'decimal:2',
    ];
    
    public function students(): HasMany
    {
        return $this->hasMany(Patient::class, 'school_id');
    }
    
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
    
    public function labTests(): HasMany
    {
        return $this->hasMany(LabTest::class);
    }
    
    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }
}