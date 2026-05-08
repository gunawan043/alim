<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jabatan extends Model
{
    use HasUuids;

    protected $table = 'jabatan';

    protected $fillable = ['jenis_gtk_id', 'nama', 'kategori', 'deskripsi', 'is_active', 'urutan'];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan'    => 'integer',
    ];

    public function jenisGtk(): BelongsTo
    {
        return $this->belongsTo(JenisGtk::class, 'jenis_gtk_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeForJenis($q, string $jenisGtkId)
    {
        return $q->where('jenis_gtk_id', $jenisGtkId);
    }
}
