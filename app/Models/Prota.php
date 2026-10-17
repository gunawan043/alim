<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * PROTA — Program Tahunan: TP/ATP × JP efektif × tahun ajaran × mapel × kelas/fase.
 * Sumber: ATP (item) + Pekan Efektif (minggu & JP efektif) — tanpa input ulang manual.
 */
class Prota extends Model
{
    use SoftDeletes;

    protected $table = 'prota';

    protected $keyType = 'string';

    public $incrementing = false;

    const STATUS_DRAFT = 'draft';

    const STATUS_FINAL = 'final';

    const STATUS_OPTIONS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_FINAL => 'Final',
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
        'atp_id',
        'minggu_efektif',
        'jp_per_minggu',
        'jp_efektif',
        'total_jp',
        'synced_at',
        'status',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'minggu_efektif' => 'integer',
        'jp_per_minggu' => 'integer',
        'jp_efektif' => 'integer',
        'total_jp' => 'integer',
        'synced_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────

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

    public function atp(): BelongsTo
    {
        return $this->belongsTo(AlurTujuanPembelajaran::class, 'atp_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProtaItem::class, 'prota_id')->orderBy('urutan');
    }

    public function prosem(): HasMany
    {
        return $this->hasMany(Prosem::class, 'prota_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────

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
