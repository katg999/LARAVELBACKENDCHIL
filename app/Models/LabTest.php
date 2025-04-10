<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    protected static function boot()
{
    parent::boot();

    static::addGlobalScope('school', function (Builder $builder) {
        if (auth()->check() && auth()->user()->school_id) {
            $builder->where('school_id', auth()->user()->school_id);
        }
    });
}
}
