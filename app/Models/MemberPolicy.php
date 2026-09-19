<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberPolicy extends Model
{
    protected $fillable = [
        'patient_id', 'insurer_id', 'member_number', 'scheme_name', 'status', 'verified_at',
        'submitted_by', 'card_image_path', 'review_note', 'reviewed_at',
    ];

    protected $casts = ['verified_at' => 'datetime', 'reviewed_at' => 'datetime'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Insurer::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
