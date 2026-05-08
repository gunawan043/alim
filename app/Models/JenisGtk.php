<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisGtk extends Model
{
    use HasUuids;

    protected $table = 'jenis_gtk';

    protected $fillable = ['nama', 'deskripsi', 'is_active', 'urutan'];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan'    => 'integer',
    ];

    public function jabatans(): HasMany
    {
        return $this->hasMany(Jabatan::class, 'jenis_gtk_id')->orderBy('urutan');
    }

    public function activeJabatans(): HasMany
    {
        return $this->hasMany(Jabatan::class, 'jenis_gtk_id')
            ->where('is_active', true)
            ->orderBy('urutan');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
