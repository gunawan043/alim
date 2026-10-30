<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TahfidzAyatPosition extends Model
{
    protected $table = 'tahfidz_ayat_positions';

    protected $fillable = [
        'surah', 'ayah',
        'page_start', 'line_start', 'page_end', 'line_end',
    ];

    protected $casts = [
        'surah' => 'integer',
        'ayah' => 'integer',
        'page_start' => 'integer',
        'line_start' => 'integer',
        'page_end' => 'integer',
        'line_end' => 'integer',
    ];
}
