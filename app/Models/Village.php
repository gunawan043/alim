<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    protected $table = 'indonesia_villages';

    protected $fillable = [
        'code',
        'district_code',
        'name',
        'meta',
    ];

    public $timestamps = false;
}
