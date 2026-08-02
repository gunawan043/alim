<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelatihan extends Model
{
    use HasUuids;

    protected $table = 'pelatihan';

    protected $fillable = [
        'nama', 'jenis_pelatihan_id', 'deskripsi', 'vendor', 'lokasi',
        'tanggal_mulai', 'tanggal_selesai', 'jumlah_jam', 'metode',
        'penggunaan_metode', 'biaya_per_peserta', 'materi_path', 'catatan',
        'status', 'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'biaya_per_peserta' => 'decimal:2',
    ];

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(JenisPelatihan::class, 'jenis_pelatihan_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function pesertas(): HasMany
    {
        return $this->hasMany(PelatihanPeserta::class);
    }

    public function evaluasis(): HasMany
    {
        return $this->hasMany(PelatihanEvaluasi::class);
    }

    public function scopeRencana($q)
    {
        return $q->where('status', 'rencana');
    }

    public function scopeSelesai($q)
    {
        return $q->where('status', 'selesai');
    }
}
