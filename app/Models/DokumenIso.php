<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenIso extends Model
{
    use HasUuids;

    protected $table = 'dokumen_iso';

    protected $fillable = [
        'nama_dokumen',
        'prosedur_konsultan',
        'pasal',
        'kode_dokumen',
        'tanggal_berlaku',
        'revisi_ke',
        'keterangan',
        'kategori',
        'link_dokumen',
        'divisi_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'tanggal_berlaku' => 'date',
        'is_active' => 'boolean',
    ];

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }
}
