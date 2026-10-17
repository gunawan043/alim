<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TahfidzSurahMaster extends Model
{
    protected $table = 'tahfidz_surah_master';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id', 'number', 'name_arabic', 'name_latin',
        'juz', 'total_ayat', 'total_halaman',
        'halaman_start', 'halaman_end', 'revelation_type',
    ];

    protected $casts = [
        'number' => 'integer',
        'juz' => 'integer',
        'total_ayat' => 'integer',
        'total_halaman' => 'decimal:1',
        'halaman_start' => 'integer',
        'halaman_end' => 'integer',
    ];
}
