<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kesejahteraan extends Model
{
    use HasUuids;

    protected $table = 'kesejahteraan';

    protected $fillable = ['nama', 'jenis', 'deskripsi', 'nilai_default', 'requires_approval', 'is_active', 'urutan'];

    protected $casts = ['nilai_default' => 'decimal:2', 'requires_approval' => 'boolean', 'is_active' => 'boolean'];

    public function penerimas(): HasMany
    {
        return $this->hasMany(KesejahteraanPenerima::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
