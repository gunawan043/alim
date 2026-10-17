<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Pendidikan extends Model
{
    protected $table = 'pendidikan';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['Strata', 'Golongan'];

    protected $casts = ['Golongan' => 'integer'];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }
}
