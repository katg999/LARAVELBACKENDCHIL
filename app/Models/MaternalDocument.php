<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaternalDocument extends Model
{
    protected $fillable = [
        'patient_id',
        'health_facility_id',
        'original_filename',
        's3_path',
        'document_type',
        'confidence'
    ];
}
