<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_type', 'actor_id', 'action', 'subject_type', 'subject_id', 'ip', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    /**
     * Record that someone viewed or changed something sensitive.
     *
     * @param array|null $actor ['type' => ..., 'id' => ...] from the session login, or null for the system
     */
    public static function record(?array $actor, string $action, ?Model $subject = null): self
    {
        return static::create([
            'actor_type' => $actor['type'] ?? 'system',
            'actor_id' => $actor['id'] ?? null,
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
