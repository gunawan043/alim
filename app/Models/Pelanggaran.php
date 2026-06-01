<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Pelanggaran extends Model
{
    use HasUuids;
    protected $table = 'pelanggaran';

    protected $fillable = ['nama', 'jenis', 'poin', 'deskripsi', 'is_active', 'urutan'];
    protected $casts = ['poin' => 'integer', 'is_active' => 'boolean'];

    public function logs(): HasMany { return $this->hasMany(PelanggaranLog::class); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
