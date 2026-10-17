<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $table = 'indonesia_provinces';

    protected $fillable = [
        'code',
        'name',
        'meta',
    ];

    public $timestamps = false;
}
