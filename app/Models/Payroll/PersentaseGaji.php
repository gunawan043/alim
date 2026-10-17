<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PersentaseGaji extends Model
{
    protected $table = 'persentase_gaji';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'persentase_golongan_1', 'persentase_golongan_2',
        'persentase_golongan_3', 'persentase_golongan_4',
    ];

    protected $casts = [
        'persentase_golongan_1' => 'decimal:2',
        'persentase_golongan_2' => 'decimal:2',
        'persentase_golongan_3' => 'decimal:2',
        'persentase_golongan_4' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }
}
