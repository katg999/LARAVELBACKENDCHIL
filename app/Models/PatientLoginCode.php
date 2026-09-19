<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientLoginCode extends Model
{
    protected $fillable = ['phone', 'code_hash', 'expires_at', 'attempts', 'used_at'];

    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];
}
