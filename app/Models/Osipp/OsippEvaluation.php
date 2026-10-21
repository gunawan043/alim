<?php

namespace App\Models\Osipp;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OsippEvaluation extends OsippModel
{
    use SoftDeletes;

    protected $table = 'osipp_evaluations';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINAL = 'final';

    public const STATUS_OPTIONS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_FINAL => 'Final',
    ];

    public const TYPE_OPTIONS = [
        'tengah' => 'Tengah Kepengurusan',
        'akhir' => 'Akhir Kepengurusan',
    ];

    /**
     * Aspek penilaian kepengurusan. Bobot berjumlah 100.
     */
    public const ASPECTS = [
        'perencanaan' => ['label' => 'Perencanaan Program', 'weight' => 20],
        'pelaksanaan' => ['label' => 'Pelaksanaan Kegiatan', 'weight' => 25],
        'administrasi' => ['label' => 'Administrasi & Dokumentasi', 'weight' => 20],
        'kepemimpinan' => ['label' => 'Kepemimpinan & Koordinasi', 'weight' => 20],
        'pelaporan' => ['label' => 'Pelaporan & Evaluasi', 'weight' => 15],
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'scores' => 'array',
        'total_score' => 'decimal:2',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(OsippPeriod::class, 'period_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForPeriod($query, string $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_OPTIONS[$this->evaluation_type] ?? ucfirst((string) $this->evaluation_type);
    }

    /**
     * Hitung nilai akhir dari skor 1–5 per aspek sesuai bobot.
     *
     * @param  array<string, int|float|string>  $scores
     */
    public static function computeTotal(array $scores): float
    {
        $total = 0.0;

        foreach (self::ASPECTS as $key => $aspect) {
            $score = max(1, min(5, (float) ($scores[$key] ?? 1)));
            $total += ($score / 5) * (float) $aspect['weight'];
        }

        return round($total, 2);
    }

    public static function gradeFor(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            default => 'D',
        };
    }
}
