<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SalaryScal extends Model
{
    protected $table = 'salary_scal';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['pendidikan', 'masa_kerja', 'gaji'];

    protected $casts = ['masa_kerja' => 'integer', 'gaji' => 'decimal:2'];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }
}
