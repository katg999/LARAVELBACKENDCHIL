<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'health_facility_id',
    ];

    public function healthFacility()
    {
        return $this->belongsTo(HealthFacility::class);
    }
}