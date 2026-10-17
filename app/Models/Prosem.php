<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * PROSEM — Program Semester: distribusi PROTA/ATP terhadap bulan & pekan efektif
 * dari Kalender Pendidikan (via Pekan Efektif) — tidak ada kalender kedua.
 */
class Prosem extends Model
{
    use SoftDeletes;

    protected $table = 'prosem';

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
        'prota_id',
        'school_id',
        'academic_year_id',
        'semester',
        'subject_id',
        'grade_level_id',
        'teacher_id',
        'synced_at',
        'adjusted_at',
        'status',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
        'adjusted_at' => 'datetime',
    ];

    public function prota(): BelongsTo
    {
        return $this->belongsTo(Prota::class, 'prota_id');
    }

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
        return $this->hasMany(ProsemItem::class, 'prosem_id')->orderBy('urutan');
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
