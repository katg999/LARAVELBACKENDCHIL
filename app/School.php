<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
     use HasFactory;

    protected $fillable = ['name', 'email', 'contact', 'document_path'];
}
