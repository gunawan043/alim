<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'indonesia_cities';

    protected $fillable = [
        'code',
        'province_code',
        'name',
        'meta',
    ];

    public $timestamps = false;
}
