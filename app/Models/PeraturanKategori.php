<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeraturanKategori extends Model
{
    use HasUuids;

    protected $table = 'peraturan_kategori';

    protected $fillable = ['nama', 'deskripsi', 'is_active', 'urutan'];

    protected $casts = ['is_active' => 'boolean'];

    public function peraturans(): HasMany
    {
        return $this->hasMany(Peraturan::class);
    }
}
