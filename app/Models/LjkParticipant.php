<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Peserta ujian LJK + pemetaan nomor lembar → butir paket.
 */
class LjkParticipant extends Model
{
    use HasFactory;

    protected $table = 'ljk_participants';

    public $incrementing = false;

    protected $keyType = 'string';

    public const STATUS_TERDAFTAR = 'terdaftar';

    public const STATUS_TERCETAK = 'tercetak';

    public const STATUS_DIPINDAI = 'dipindai';

    public const STATUS_DIVERIFIKASI = 'diverifikasi';

    public const STATUS_FINAL = 'final';

    public const STATUS_OPTIONS = [
        self::STATUS_TERDAFTAR => 'Terdaftar',
        self::STATUS_TERCETAK => 'Lembar dicetak',
        self::STATUS_DIPINDAI => 'Sudah dipindai',
        self::STATUS_DIVERIFIKASI => 'Diverifikasi',
        self::STATUS_FINAL => 'Final',
    ];

    protected $fillable = [
        'ljk_exam_id', 'student_id', 'study_group_id', 'nomor_peserta',
        'mapping', 'status', 'sheet_generated_at', 'total_point', 'nilai_akhir',
    ];

    protected $casts = [
        'mapping' => 'array',
        'sheet_generated_at' => 'datetime',
        'total_point' => 'integer',
        'nilai_akhir' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) \Illuminate\Support\Str::uuid());
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(LjkExam::class, 'ljk_exam_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function sheet(): HasOne
    {
        return $this->hasOne(LjkSheet::class, 'participant_id');
    }

    /**
     * Butir paket sesuai urutan lembar peserta.
     *
     * @return array<int, array<string, mixed>>
     */
    public function mappingItems(): array
    {
        return is_array($this->mapping) ? ($this->mapping['items'] ?? []) : [];
    }

    public function needsReview(): bool
    {
        return in_array($this->status, [self::STATUS_DIPINDAI], true);
    }
}
