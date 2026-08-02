<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KinerjaPenilaian extends Model
{
    use HasUuids;

    protected $table = 'kinerja_penilaian';

    protected $fillable = [
        'user_id', 'kinerja_periode_id', 'penilai_id', 'total_skor',
        'nilai_huruf', 'kategori_hasil', 'catatan_penilai', 'catatan_rekonsiliasi',
        'status', 'tanggal_penilaian', 'nilai_detail', 'rekomendasi', 'status_rekomendasi',
    ];

    protected $casts = [
        'total_skor' => 'decimal:2',
        'tanggal_penilaian' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(KinerjaPeriode::class, 'kinerja_periode_id');
    }

    public function penilai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penilai_id');
    }

    public function skors(): HasMany
    {
        return $this->hasMany(KinerjaSkor::class);
    }

    public static function hitungNilaiHuruf(float $skor): string
    {
        return match (true) {
            $skor >= 90 => 'A',
            $skor >= 80 => 'B',
            $skor >= 70 => 'C',
            default => 'D',
        };
    }

    public static function hitungKategori(string $nilaiHuruf): string
    {
        return match ($nilaiHuruf) {
            'A' => 'Sangat Baik',
            'B' => 'Baik',
            'C' => 'Cukup',
            'D' => 'Kurang',
        };
    }
}
