<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'health_facility_id',
        'name',
        'gender',
        'birth_date',
        'contact_number',
        'medical_history'
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function healthFacility()
    {
        return $this->belongsTo(HealthFacility::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
    public function maternalDocuments()
{
    return $this->hasMany(MaternalDocument::class);
}
}