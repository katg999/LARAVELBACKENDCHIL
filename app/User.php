<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'is_admin', 'school_id', 'health_facility_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    /**
     * Get the roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Role::class);
    }

    /**
     * Get the permissions through roles.
     */
    public function getPermissionsAttribute()
    {
        $permissions = collect();

        foreach ($this->roles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }

        return $permissions->unique('id');
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions->contains('slug', $permissionSlug);
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string $roleSlug): void
    {
        $role = \App\Models\Role::where('slug', $roleSlug)->first();
        if ($role && !$this->hasRole($roleSlug)) {
            $this->roles()->attach($role);
        }
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string $roleSlug): void
    {
        $role = \App\Models\Role::where('slug', $roleSlug)->first();
        if ($role) {
            $this->roles()->detach($role);
        }
    }

    /**
     * Get the school that the user belongs to.
     */
    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }

    /**
     * Get the health facility that the user belongs to.
     */
    public function healthFacility()
    {
        return $this->belongsTo(\App\Models\HealthFacility::class);
    }
}
