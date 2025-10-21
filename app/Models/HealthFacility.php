<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HealthFacility extends Model
{

    use HasFactory;
     protected $fillable = ['name', 'email', 'contact', 'file_url', 'wallet_balance'];

     protected $casts = [
         'wallet_balance' => 'decimal:2',
     ];


     public function patients()
{
    return $this->hasMany(Patient::class);
}

}
