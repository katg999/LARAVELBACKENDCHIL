<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'verification_token',
        'verified_at',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'verification_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Generate a new verification token.
     *
     * @return string
     */
    public static function generateVerificationToken()
    {
        return \Illuminate\Support\Str::random(60);
    }

    /**
     * Verify the subscriber.
     *
     * @return bool
     */
    public function verify()
    {
        return $this->update([
            'verified_at' => now(),
            'is_active' => true,
            'verification_token' => null,
        ]);
    }

    /**
     * Check if the subscriber is verified.
     *
     * @return bool
     */
    public function isVerified()
    {
        return !is_null($this->verified_at);
    }

    /**
     * Scope a query to only include active subscribers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include verified subscribers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }
}