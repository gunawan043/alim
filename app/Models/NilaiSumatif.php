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
        'rs',
        'sts',
        'raport_sts',
        'sas',
        'rsa',
        'nr_murni',
        'nr_final',
        'ket',
    ];

    protected $casts = [
        's1' => 'decimal:2',
        's2' => 'decimal:2',
        's3' => 'decimal:2',
        's4' => 'decimal:2',
        's5' => 'decimal:2',
        's6' => 'decimal:2',
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

    // Auto-calculate: RS = average of S1-S6
    public static function calcRs(array $s): ?float
    {
        $values = array_filter([$s['s1'] ?? null, $s['s2'] ?? null, $s['s3'] ?? null,
            $s['s4'] ?? null, $s['s5'] ?? null, $s['s6'] ?? null]);

        return count($values) ? round(array_sum($values) / count($values), 2) : null;
    }

    // Auto-calculate: RSA = (STS + SAS) / 2
    // Jika $raportSts diberikan (bukan null),用它替代 $sts untuk hitungan raport
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

    // Batch-recalculate NR Final for all students in a book when weights change
    public static function recalcNrFinalByBook(string $adminBookId, float $wRs, float $wSts, float $wSas): int
    {
        $updated = 0;
        static::where('admin_book_id', $adminBookId)->each(function ($row) use ($wRs, $wSts, $wSas, &$updated) {
            $nrFinal = static::calcNrFinal(
                $row->rs !== null ? (float) $row->rs : null,
                $row->sts !== null ? (float) $row->sts : null,
                $row->sas !== null ? (float) $row->sas : null,
                $wRs, $wSts, $wSas,
                $row->raport_sts !== null ? (float) $row->raport_sts : null
            );
            if ($nrFinal !== null) {
                $row->nr_final = $nrFinal;
                $row->save();
                $updated++;
            }
        });

        return $updated;
    }
}
