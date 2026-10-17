<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SkalaGajiGuru extends Model
{
    protected $table = 'skala_gaji_guru';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'masa_kerja', 'level',
        'golongan_1', 'golongan_2', 'golongan_3', 'golongan_4', 'golongan_5',
    ];

    protected $casts = [
        'masa_kerja' => 'integer',
        'level' => 'integer',
        'golongan_1' => 'decimal:2',
        'golongan_2' => 'decimal:2',
        'golongan_3' => 'decimal:2',
        'golongan_4' => 'decimal:2',
        'golongan_5' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }
}
