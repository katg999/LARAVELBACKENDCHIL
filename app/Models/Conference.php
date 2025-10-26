<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Conference extends Model
{
    protected $fillable = [
        'appointment_id',
        'room_name',
        'room_token',
        'status',
        'host_id',
        'max_participants',
        'settings',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'max_participants' => 'integer',
        'audio_enabled' => 'boolean',
        'video_enabled' => 'boolean',
    ];

    // Relationships
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConferenceParticipant::class);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function canJoin(User $user): bool
    {
        // Check if user is the doctor (host) or the patient
        return $this->host_id === $user->id ||
               ($this->appointment && $this->appointment->patient_id === $user->id);
    }

    public function isHost(User $user): bool
    {
        return $this->host_id === $user->id;
    }

    public function start(): void
    {
        $this->update([
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    public function end(): void
    {
        $this->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        // Update all participants as left
        $this->participants()->where('status', 'joined')->update([
            'status' => 'left',
            'left_at' => now(),
        ]);
    }

    public function generateRoomName(): string
    {
        return 'conference_' . $this->appointment_id . '_' . Str::random(8);
    }

    // Static method to create conference for appointment
    public static function createForAppointment(Appointment $appointment): self
    {
        return static::create([
            'appointment_id' => $appointment->id,
            'room_name' => 'conference_' . $appointment->id . '_' . Str::random(8),
            'host_id' => $appointment->doctor_id,
            'status' => 'scheduled',
            'max_participants' => 2, // Doctor + 1 patient
        ]);
    }
}
