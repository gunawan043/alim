<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NilaiSumatif extends Model
{
    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $table = 'admin_nilai_sumatif';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'admin_book_id',
        'student_id',
        'academic_year_id',
        'semester',
        's1', 's2', 's3', 's4', 's5', 's6',
        'sumatif_harian',
        'rs',
        'sts',
        'raport_sts',
        'sas',
        'rsa',
        'nr_murni',
        'nr_final',
        'ket',
        'paket_soal_id',
    ];

    protected $casts = [
        's1' => 'decimal:2',
        's2' => 'decimal:2',
        's3' => 'decimal:2',
        's4' => 'decimal:2',
        's5' => 'decimal:2',
        's6' => 'decimal:2',
        'sumatif_harian' => 'array',
        'rs' => 'decimal:2',
        'sts' => 'decimal:2',
        'raport_sts' => 'decimal:2',
        'sas' => 'decimal:2',
        'rsa' => 'decimal:2',
        'nr_murni' => 'decimal:2',
        'nr_final' => 'decimal:2',
    ];

    public function adminBook(): BelongsTo
    {
        return $this->belongsTo(TeacherAdminBook::class, 'admin_book_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    // Auto-calculate: RS = rata-rata semua kolom Sumatif Harian yang terisi.
    // Aturan tunggal ada di SumatifHarianService (legacy S1–S6 + kolom dinamis).
    public static function calcRs(array $values): ?float
    {
        return app(\App\Services\SumatifHarianService::class)->calcRs($values);
    }

    // Auto-calculate: RSA = (STS + SAS) / 2
    // Jika $raportSts diberikan (bukan null), gunakan sebagai pengganti $sts untuk hitungan raport
    public static function calcRsa(?float $sts, ?float $sas, ?float $raportSts = null): ?float
    {
        $effectiveSts = $raportSts ?? $sts;
        if ($effectiveSts === null || $sas === null) {
            return null;
        }

        return round(($effectiveSts + $sas) / 2, 2);
    }

    // Auto-calculate: NR Murni = (RS + RSA) / 2
    public static function calcNrMurni(?float $rs, ?float $rsa): ?float
    {
        if ($rs === null || $rsa === null) {
            return null;
        }

        return round(($rs + $rsa) / 2, 2);
    }

    // Auto-calculate: NR Final = (RS × wRs + STS × wSts + SAS × wSas) / 100
    // Gunakan $raportSts jika ada, fallback ke $sts
    public static function calcNrFinal(?float $rs, ?float $sts, ?float $sas, float $wRs, float $wSts, float $wSas, ?float $raportSts = null): ?float
    {
        $effectiveSts = $raportSts ?? $sts;
        if ($rs === null || $effectiveSts === null || $sas === null) {
            return null;
        }

        return round(($rs * $wRs + $effectiveSts * $wSts + $sas * $wSas) / 100, 2);
    }

    // Batch-recalculate seluruh nilai turunan satu buku (aturan tunggal dari service).
    public static function recalcNrFinalByBook(string $adminBookId, float $wRs, float $wSts, float $wSas): int
    {
        $book = TeacherAdminBook::find($adminBookId);

        if (! $book) {
            return 0;
        }

        // Bobot sudah disimpan oleh pemanggil — hitung ulang semua nilai turunan.
        return app(\App\Services\SumatifHarianService::class)->recalcByBook($book);
    }
}
