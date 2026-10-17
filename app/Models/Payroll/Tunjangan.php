<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tunjangan extends Model
{
    protected $table = 'tunjangan';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'Kode', 'Subjek', 'Nominal',
        'is_active', 'is_attendance_based', 'per_hari',
    ];

    protected $casts = [
        'Nominal' => 'decimal:2',
        'is_active' => 'boolean',
        'is_attendance_based' => 'boolean',
        'per_hari' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }
}
