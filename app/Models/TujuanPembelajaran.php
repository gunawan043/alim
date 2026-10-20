<?php

namespace App\Models;

use App\Models\Concerns\HasCurriculumProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Tujuan Pembelajaran (TP) — diturunkan dari Capaian Pembelajaran (CP),
 * disusun ke dalam ATP, lalu dipakai perangkat pembelajaran & asesmen.
 *
 * Skema fisik mengikuti migrasi 2026_06_19_010001 (+ tautan CP pada
 * migrasi 2026_10_09_100000).
 */
class TujuanPembelajaran extends Model
{
    use HasCurriculumProfile;

    use SoftDeletes;

    protected $table = 'tujuan_pembelajaran';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static $bootDefault;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'curriculum_profile_id',
        'id',
        'school_id',
        'capaian_pembelajaran_id',
        'subject_id',
        'grade_level_id',
        'academic_year_id',
        'semester',
        'fase',
        'kode_tp',
        'deskripsi',
        'elemen',
        'kbc_values',
        'kbc_indicator',
        'alokasi_waktu',
        'urutan',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'alokasi_waktu' => 'integer',
        'urutan' => 'integer',
        'is_active' => 'boolean',
        'kbc_values' => 'array',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function curriculumProfile(): BelongsTo
    {
        return $this->belongsTo(CurriculumProfile::class, 'curriculum_profile_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'grade_level_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function capaianPembelajaran(): BelongsTo
    {
        return $this->belongsTo(CapaianPembelajaran::class, 'capaian_pembelajaran_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function atpItems(): HasMany
    {
        return $this->hasMany(AlurTujuanPembelajaranItem::class, 'tujuan_pembelajaran_id');
    }

    public function soalOptions(): HasMany
    {
        return $this->hasMany(SoilOption::class, 'tp_id', 'id');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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

    // ── Accessors (kompatibilitas label lama) ────────────────────────

    public function getTpAttribute(): ?string
    {
        return $this->deskripsi;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->deskripsi;
    }
}
