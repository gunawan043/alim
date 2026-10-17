<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SuratTemplate extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['school_id', 'jenis', 'nama', 'perihal_template', 'isi_template', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
