<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bpjs extends Model
{
    use HasUuids;

    protected $table = 'bpjs';

    protected $fillable = [
        'user_id', 'nomor_kartu', 'jenis_bpjs', 'tanggal_daftar', 'tanggal_nonaktif',
        'iuran_per_bulan', 'iuran_perusahaan', 'iuran_pekerja', 'status', 'catatan',
    ];

    protected $casts = [
        'tanggal_daftar' => 'date',
        'tanggal_nonaktif' => 'date',
        'iuran_per_bulan' => 'decimal:2',
        'iuran_perusahaan' => 'decimal:2',
        'iuran_pekerja' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
