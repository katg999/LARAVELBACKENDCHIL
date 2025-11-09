<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SchoolInvitation extends Model
{
    protected $fillable = [
        'school_id',
        'email',
        'role',
        'token',
        'expires_at',
        'accepted',
        'accepted_by',
        'accepted_at',
        'invited_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'accepted' => 'boolean',
    ];

    /**
     * Generate a unique invitation token
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Check if invitation is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if invitation is still valid
     */
    public function isValid(): bool
    {
        return !$this->accepted && !$this->isExpired();
    }

    /**
     * Get the school that owns the invitation
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the user who sent the invitation
     */
    public function inviter()
    {
        return $this->belongsTo(\App\Models\User::class, 'invited_by');
    }

    /**
     * Get the user who accepted the invitation
     */
    public function acceptedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'accepted_by');
    }
}
