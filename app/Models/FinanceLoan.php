<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinanceLoan extends Model
{
     use HasFactory;
    //

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'facility_name',
        'facility_report_path',
        'license_path',
    ];
}

