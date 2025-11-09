<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class School extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'contact', 'address', 'file_url'];
    
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

    /**
     * Get all users associated with this school
     */
    public function users()
    {
        return $this->hasMany(\App\Models\User::class);
    }

    /**
     * Get all invitations for this school
     */
    public function invitations()
    {
        return $this->hasMany(SchoolInvitation::class);
    }

    /**
     * Get pending invitations
     */
    public function pendingInvitations()
    {
        return $this->invitations()
            ->where('accepted', false)
            ->where('expires_at', '>', now());
    }

    /**
     * Get admin users for this school
     */
    public function admins()
    {
        return $this->users()->whereHas('roles', function ($query) {
            $query->where('slug', 'school-admin');
        });
    }

    /**
     * Get staff users for this school
     */
    public function staff()
    {
        return $this->users()->whereHas('roles', function ($query) {
            $query->where('slug', 'school-staff');
        });
    }
}