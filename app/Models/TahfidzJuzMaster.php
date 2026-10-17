<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TahfidzJuzMaster extends Model
{
    protected $table = 'tahfidz_juz_master';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id', 'juz_number', 'name',
        'halaman_start', 'halaman_end', 'total_halaman',
        'surah_start_id', 'ayat_start', 'surah_end_id', 'ayat_end',
    ];

    protected $casts = [
        'juz_number' => 'integer',
        'halaman_start' => 'integer',
        'halaman_end' => 'integer',
        'total_halaman' => 'integer',
        'ayat_start' => 'integer',
        'ayat_end' => 'integer',
    ];
}
