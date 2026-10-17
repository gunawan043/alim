<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = 'indonesia_districts';

    protected $fillable = [
        'code',
        'city_code',
        'name',
        'meta',
    ];

    public $timestamps = false;
}
