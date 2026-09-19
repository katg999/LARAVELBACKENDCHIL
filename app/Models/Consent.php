<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consent extends Model
{
    protected $fillable = ['patient_id', 'appointment_id', 'type', 'ip', 'granted_at'];

    protected $casts = ['granted_at' => 'datetime'];
}
