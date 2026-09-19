<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    public const DELIVERY_FLOW = [
        'requested' => ['preparing', 'cancelled'],
        'preparing' => ['out_for_delivery', 'cancelled'],
        'out_for_delivery' => ['delivered', 'cancelled'],
    ];

    protected $fillable = [
        'patient_id', 'appointment_id', 'doctor_id', 'source', 'status', 'notes', 'review_note',
        'image_path', 'delivery_status', 'delivery_address', 'delivery_phone', 'delivery_requested_at',
    ];

    protected $casts = ['delivery_requested_at' => 'datetime'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    /** A prescription can be delivered once a doctor wrote it or approved an uploaded one. */
    public function canRequestDelivery(): bool
    {
        return in_array($this->status, ['issued', 'approved'], true) && $this->delivery_status === null;
    }
}
