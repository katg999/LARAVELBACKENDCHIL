<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Message extends Model
{
    use HasFactory;

    // Define the table name if different from the default
    protected $table = 'messages';

    // Fillable fields for mass assignment
    protected $fillable = ['health_facility_id', 'content', 'is_read'];

    // Define the relationship with HealthFacility
    public function healthFacility()
    {
        return $this->belongsTo(HealthFacility::class);
    }
}