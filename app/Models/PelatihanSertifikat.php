<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PelatihanSertifikat extends Model
{
    use HasUuids;
    protected $table = 'pelatihan_sertifikat';

    protected $fillable = ['pelatihan_peserta_id', 'nomor_sertifikat', 'tanggal_terbit', 'tanggal_expired', 'dokumen_path', 'notes'];

    protected $casts = ['tanggal_terbit' => 'date', 'tanggal_expired' => 'date'];

    public function peserta(): BelongsTo { return $this->belongsTo(PelatihanPeserta::class); }
}