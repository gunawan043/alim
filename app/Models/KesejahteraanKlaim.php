<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KesejahteraanKlaim extends Model
{
    use HasUuids;

    protected $table = 'kesejahteraan_klaim';

    protected $fillable = [
        'user_id', 'kesejahteraan_id', 'nomor_klaim', 'nilai_diminta', 'nilai_disetujui',
        'deskripsi_kejadian', 'dokumen_path', 'status', 'catatan_admin', 'diproses_oleh', 'diproses_at',
    ];

    protected $casts = ['nilai_diminta' => 'decimal:2', 'nilai_disetujui' => 'decimal:2', 'diproses_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kesejahteraan(): BelongsTo
    {
        return $this->belongsTo(Kesejahteraan::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }
}
