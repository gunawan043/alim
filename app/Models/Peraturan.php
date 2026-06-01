<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Peraturan extends Model
{
    use HasUuids;
    protected $table = 'peraturan';

    protected $fillable = [
        'peraturan_kategori_id', 'judul', 'deskripsi', 'nomor_dokumen',
        'tanggal_berlaku', 'tanggal_expired', 'dokumen_path', 'versi',
        'status', 'catatan_perubahan', 'dibuat_oleh', 'ditandatangani_oleh',
    ];
    protected $casts = ['tanggal_berlaku' => 'date', 'tanggal_expired' => 'date'];

    public function kategori(): BelongsTo { return $this->belongsTo(PeraturanKategori::class, 'peraturan_kategori_id'); }
    public function pembuat(): BelongsTo { return $this->belongsTo(User::class, 'dibuat_oleh'); }
    public function penandaTangan(): BelongsTo { return $this->belongsTo(User::class, 'ditandatangani_oleh'); }
    public function readLogs(): HasMany { return $this->hasMany(PeraturanReadLog::class); }
}
