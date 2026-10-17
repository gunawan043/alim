<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Hasil pembacaan OMR per nomor soal.
 *
 * Baris ini menyimpan HASIL AWAL mesin. Koreksi guru dicatat di
 * ljk_answer_corrections (append-only); kolom review_* menunjuk kondisi
 * terkini setelah pemeriksaan.
 */
class LjkScanAnswer extends Model
{
    use HasFactory;

    protected $table = 'ljk_scan_answers';

    public $incrementing = false;

    protected $keyType = 'string';

    public const TERBACA = 'terbaca';

    public const KOSONG = 'kosong';

    public const GANDA = 'ganda';

    public const MERAGUKAN = 'meragukan';

    public const TIDAK_DAPAT_DIPROSES = 'tidak_dapat_diproses';

    public const MANUAL = 'manual';

    public const STATUSES = [
        self::TERBACA, self::KOSONG, self::GANDA,
        self::MERAGUKAN, self::TIDAK_DAPAT_DIPROSES, self::MANUAL,
    ];

    /** Status yang wajib diperiksa guru sebelum finalisasi. */
    public const REVIEW_STATUSES = [self::GANDA, self::MERAGUKAN, self::TIDAK_DAPAT_DIPROSES, self::MANUAL];

    protected $fillable = [
        'ljk_scan_id', 'ljk_exam_id', 'participant_id', 'nomor',
        'item_id', 'soal_id', 'jenis', 'opsi', 'status', 'selected_label',
        'confidence', 'evidence', 'is_correct', 'point_awal', 'point_final',
        'needs_review', 'review_action', 'reviewed_by', 'reviewed_at', 'teacher_note',
    ];

    protected $casts = [
        'nomor' => 'integer',
        'opsi' => 'array',
        'evidence' => 'array',
        'confidence' => 'decimal:4',
        'is_correct' => 'boolean',
        'point_awal' => 'decimal:2',
        'point_final' => 'decimal:2',
        'needs_review' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(LjkScan::class, 'ljk_scan_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(LjkExam::class, 'ljk_exam_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(LjkParticipant::class, 'participant_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(LjkAnswerCorrection::class, 'ljk_scan_answer_id')->orderBy('created_at');
    }

    public function statusLabel(): string
    {
        return config("ljk.statuses.{$this->status}", $this->status);
    }

    public function isReadable(): bool
    {
        return in_array($this->status, [self::TERBACA, self::KOSONG], true);
    }
}
