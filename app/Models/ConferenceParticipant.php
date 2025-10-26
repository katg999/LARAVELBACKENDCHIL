<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConferenceParticipant extends Model
{
    protected $fillable = [
        'conference_id',
        'user_id',
        'role',
        'status',
        'joined_at',
        'left_at',
        'audio_enabled',
        'video_enabled',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'audio_enabled' => 'boolean',
        'video_enabled' => 'boolean',
    ];

    // Relationships
    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function isHost(): bool
    {
        return $this->role === 'host';
    }

    public function isActive(): bool
    {
        return $this->status === 'joined';
    }

    public function join(): void
    {
        $this->update([
            'status' => 'joined',
            'joined_at' => now(),
        ]);
    }

    public function leave(): void
    {
        $this->update([
            'status' => 'left',
            'left_at' => now(),
        ]);
    }

    public function kick(): void
    {
        $this->update([
            'status' => 'kicked',
            'left_at' => now(),
        ]);
    }
}
