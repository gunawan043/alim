<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Masakerja extends Model
{
    protected $table = 'masakerja';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['masa_kerja', 'level'];

    protected $casts = ['masa_kerja' => 'integer', 'level' => 'integer'];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }
}
