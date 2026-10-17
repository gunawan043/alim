<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * ATP (Alur Tujuan Pembelajaran) — susunan sistematis TP untuk satu
 * mapel + jenjang pada satu semester, terhubung dengan JP efektif
 * (minggu efektif × JP per minggu).
 */
class AlurTujuanPembelajaran extends Model
{
    use SoftDeletes;

    protected $table = 'alur_tujuan_pembelajaran';

    protected $keyType = 'string';

    public $incrementing = false;

    const STATUS_DRAFT = 'draft';

    const STATUS_PUBLISHED = 'published';

    const STATUS_OPTIONS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PUBLISHED => 'Terbit',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'id',
        'school_id',
        'academic_year_id',
        'semester',
        'subject_id',
        'grade_level_id',
        'fase',
        'teacher_id',
        'total_jp',
        'status',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'total_jp' => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'grade_level_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AlurTujuanPembelajaranItem::class, 'alur_tujuan_pembelajaran_id')
            ->orderBy('urutan');
    }

    public function perangkat(): HasMany
    {
        return $this->hasMany(PerangkatPembelajaran::class, 'atp_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Hitung ulang total JP alokasi dari item lalu simpan bila berubah.
     */
    public function recalculateTotal(): int
    {
        $total = (int) $this->items()->sum('jp_alokasi');

        if ($this->total_jp !== $total) {
            $this->forceFill(['total_jp' => $total])->save();
        }

        return $total;
    }

    public function scopeBySchool($query, ?string $schoolId)
    {
        return $schoolId ? $query->where('school_id', $schoolId) : $query;
    }

    public function scopeByAcademicYear($query, ?string $academicYearId)
    {
        return $academicYearId ? $query->where('academic_year_id', $academicYearId) : $query;
    }

    public function scopeBySemester($query, ?string $semester)
    {
        return $semester ? $query->where('semester', $semester) : $query;
    }
}
