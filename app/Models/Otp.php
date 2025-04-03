<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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




