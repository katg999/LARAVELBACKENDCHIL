<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HealthFacility extends Model
{

    use HasFactory;
     protected $fillable = ['name', 'email', 'contact', 'file_url'];


     public function patients()
{
    return $this->hasMany(Patient::class);
}

     public function doctors()
{
    return $this->hasMany(Doctor::class);
}

    /**
     * Get all users associated with this health facility
     */
    public function users()
    {
        return $this->hasMany(\App\Models\User::class);
    }

    /**
     * Get all invitations for this health facility
     */
    public function invitations()
    {
        return $this->hasMany(HealthFacilityInvitation::class);
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
     * Get admin users for this health facility
     */
    public function admins()
    {
        return $this->users()->whereHas('roles', function ($query) {
            $query->where('slug', 'health-facility-admin');
        });
    }

    /**
     * Get medical personnel for this health facility
     */
    public function medicalPersonnel()
    {
        return $this->users()->whereHas('roles', function ($query) {
            $query->where('slug', 'health-facility-medical-personnel');
        });
    }

}
