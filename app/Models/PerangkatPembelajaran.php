<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Perangkat Pembelajaran — fondasi perencanaan pembelajaran turunan ATP.
 *
 * Desain pembelajarannya dirancang mengikuti prinsip Pembelajaran Mendalam:
 * berkesadaran (pertanyaan pemantik, pemahaman bermakna), bermakna
 * (konteks nyata, asesmen), dan menggembirakan (pengalaman memahami →
 * mengaplikasi → merefleksi, diferensiasi).
 */
class PerangkatPembelajaran extends Model
{
    use SoftDeletes;

    protected $table = 'perangkat_pembelajaran';

    protected $keyType = 'string';

    public $incrementing = false;

    const STATUS_DRAFT = 'draft';

    const STATUS_FINAL = 'final';

    const STATUS_OPTIONS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_FINAL => 'Final',
    ];

    /**
     * Bagian desain pembelajaran (kunci JSON `desain` → label tampilan).
     * Struktur ini yang memastikan dimensi pembelajaran mendalam melekat
     * di perencanaan, bukan sekadar field bernama "pembelajaran mendalam".
     */
    const DESAIN_SECTIONS = [
        'pertanyaan_pemantik' => 'Pertanyaan Pemantik',
        'pemahaman_bermakna' => 'Pemahaman Bermakna',
        'pengalaman_memahami' => 'Pengalaman Belajar — Memahami',
        'pengalaman_mengaplikasi' => 'Pengalaman Belajar — Mengaplikasi',
        'pengalaman_refleksi' => 'Pengalaman Belajar — Merefleksi',
        'konteks_nyata' => 'Koneksi Konteks Nyata',
        'asesmen_formatif' => 'Asesmen Formatif',
        'asesmen_sumatif' => 'Asesmen Sumatif',
        'diferensiasi' => 'Diferensiasi',
        'media_sumber' => 'Media & Sumber Belajar',
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
        'study_group_id',
        'atp_id',
        'teacher_id',
        'judul',
        'status',
        'desain',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'desain' => 'array',
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

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    public function atp(): BelongsTo
    {
        return $this->belongsTo(AlurTujuanPembelajaran::class, 'atp_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Desain default dengan seluruh bagian Pembelajaran Mendalam.
     *
     * @return array<string, string|null>
     */
    public static function defaultDesain(): array
    {
        return array_fill_keys(array_keys(self::DESAIN_SECTIONS), null);
    }

    /**
     * Ambil nilai satu bagian desain.
     */
    public function desainValue(string $key): ?string
    {
        $desain = $this->desain ?? [];

        return filled($desain[$key] ?? null) ? (string) $desain[$key] : null;
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
