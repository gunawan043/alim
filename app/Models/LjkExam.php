<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Sesi ujian LJK — referensi ke Paket Soal (sumber kebenaran soal).
 *
 * Isi soal & kunci TIDAK disalin ke tabel ini. Yang dibekukan adalah
 * identitas versi: paket_soal_id, paket_version, paket_snapshot_at, dan
 * paket_digest (sha256 naskah beku pada saat LJK diterbitkan).
 */
class LjkExam extends Model
{
    use HasFactory, LogsDeletion, SoftDeletes;

    protected $table = 'ljk_exams';

    public $incrementing = false;

    protected $keyType = 'string';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_PENGUMPULAN = 'pengumpulan';

    public const STATUS_REVIEW = 'review';

    public const STATUS_FINAL = 'final';

    public const STATUS_TERSINKRON = 'tersinkron';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const STATUS_OPTIONS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_AKTIF => 'Siap Dipindai',
        self::STATUS_PENGUMPULAN => 'Pengumpulan',
        self::STATUS_REVIEW => 'Perlu Pemeriksaan',
        self::STATUS_FINAL => 'Final',
        self::STATUS_TERSINKRON => 'Tersinkron ke Nilai',
        self::STATUS_DIBATALKAN => 'Dibatalkan',
    ];

    public const JENIS_UJIAN = ['sts', 'sas', 'ulangan_harian', 'try_out', 'latihan'];

    protected $fillable = [
        'code', 'school_id', 'academic_year_id', 'semester', 'admin_book_id',
        'paket_soal_id', 'paket_version', 'paket_snapshot_at', 'paket_digest',
        'study_group_id', 'subject_id', 'template_id', 'jenis_ujian',
        'judul', 'tanggal', 'waktu_mulai', 'waktu_selesai', 'jumlah_soal', 'skor_config', 'nilai_target',
        'status', 'instruksi', 'created_by',
    ];

    protected $casts = [
        'paket_version' => 'integer',
        'paket_snapshot_at' => 'datetime',
        'tanggal' => 'date',
        'waktu_mulai' => 'datetime:H:i',
        'waktu_selesai' => 'datetime:H:i',
        'jumlah_soal' => 'integer',
        'skor_config' => 'array',
        'nilai_target' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->id = $m->id ?: (string) Str::uuid();
            if (empty($m->code)) {
                $m->code = static::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        return 'LJK-'.now()->format('ymd').'-'.strtoupper(Str::random(6));
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function adminBook(): BelongsTo
    {
        return $this->belongsTo(TeacherAdminBook::class, 'admin_book_id');
    }

    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class, 'paket_soal_id');
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LjkTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(LjkParticipant::class, 'ljk_exam_id');
    }

    public function sheets(): HasMany
    {
        return $this->hasMany(LjkSheet::class, 'ljk_exam_id');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(LjkScan::class, 'ljk_exam_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(LjkSyncLog::class, 'ljk_exam_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_AKTIF, self::STATUS_PENGUMPULAN, self::STATUS_REVIEW]);
    }

    public function canScan(): bool
    {
        return in_array($this->status, [self::STATUS_AKTIF, self::STATUS_PENGUMPULAN, self::STATUS_REVIEW], true);
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, [self::STATUS_FINAL, self::STATUS_TERSINKRON], true);
    }

    public function statusLabel(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? $this->status;
    }

    /**
     * Susunan skor: total bobot & mode.
     *
     * @return array{total_weight: float, scale: float, manual_count: int}
     */
    public function scoringSummary(): array
    {
        $config = $this->skor_config ?? [];

        return [
            'total_weight' => (float) ($config['total_weight'] ?? 0),
            'scale' => (float) ($config['scale'] ?? config('ljk.grading.scale', 100.0)),
            'manual_count' => (int) ($config['manual_count'] ?? 0),
        ];
    }
}
