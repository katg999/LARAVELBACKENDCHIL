<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Insurer extends Model
{
    protected $fillable = ['name', 'code', 'handoff_method', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function policies(): HasMany
    {
        return $this->hasMany(MemberPolicy::class);
    }
}
