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
        'coverage_type', 'member_policy_id', 'total_amount', 'insurer_amount', 'patient_amount',
        'insurance_status', 'insurer_reference', 'insurance_note',
        'payment_status', 'payment_method', 'payment_reference', 'priced_at',
        'claim_status', 'claim_reference', 'claim_note', 'claim_paid_amount', 'claim_submitted_at', 'claim_updated_at',
    ];

    protected $casts = [
        'delivery_requested_at' => 'datetime',
        'priced_at' => 'datetime',
        'claim_submitted_at' => 'datetime',
        'claim_updated_at' => 'datetime',
        'claim_paid_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'insurer_amount' => 'decimal:2',
        'patient_amount' => 'decimal:2',
    ];

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

    public function memberPolicy(): BelongsTo
    {
        return $this->belongsTo(MemberPolicy::class);
    }

    /** Written by a doctor or approved by a clinician, priced by the pharmacy, and the patient's share settled. */
    public function canRequestDelivery(): bool
    {
        return in_array($this->status, ['issued', 'approved'], true)
            && $this->delivery_status === null
            && $this->payment_status === 'paid';
    }

    /** Amount the patient still has to pay, or null before pricing. */
    public function amountDue(): ?float
    {
        return $this->payment_status === 'paid' || $this->patient_amount === null ? null : (float) $this->patient_amount;
    }
}
