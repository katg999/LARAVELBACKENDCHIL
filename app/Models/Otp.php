<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
     use HasFactory;

    protected $fillable = [
        'school_id',
        'code',
        'expires_at',
        'used'
    ];
}
