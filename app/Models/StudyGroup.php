<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StudyGroup extends Model
{
    use LogsDeletion;

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id', 'unit_type', 'data_owner_school_id', 'academic_year_id', 'grade_level_id',
        'homeroom_teacher_id', 'homeroom_name', 'name', 'code',
        'capacity', 'room', 'curriculum_type', 'shift',
        'is_active', 'notes',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'unit_type' => 'string',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function dataOwnerSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'data_owner_school_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    public function studentClassHistories(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    public function jadwalKbms(): HasMany
    {
        return $this->hasMany(JadwalKbm::class, 'study_group_id');
    }

    // ── Accessors ────────────────────────────────────────────────

    /**
     * Urut alami: tingkat (level) lalu nama rombel (1A, 1B, 2A, …).
     */
    public function scopeOrderedByGrade($query)
    {
        // Subquery — tanpa join agar tidak menimbulkan kolom ambigu pada paginate/count.
        return $query
            ->orderByRaw('(SELECT level FROM grade_levels WHERE grade_levels.id = study_groups.grade_level_id) ASC')
            ->orderBy('study_groups.name');
    }

    public function getFullNameAttribute(): string
    {
        // Nama rombel sudah memuat tingkat (mis. "1A", "7B") — jangan menempelkan tingkat dua kali.
        return $this->name;
    }

    /**
     * Nama wali kelas untuk dokumen rombel (rapor/leger).
     *
     * Memakai nama khusus rombel bila diisi (mis. lengkap dengan gelar), atau
     * nama GTK dengan kapital di awal kata. Tidak mengubah data pribadi wali.
     */
    public function homeroomDisplayName(): ?string
    {
        if (filled($this->homeroom_name)) {
            return trim((string) $this->homeroom_name);
        }

        $name = $this->homeroomTeacher?->name;

        if (! filled($name)) {
            return null;
        }

        $name = trim((string) $name);

        // Data GTK sering tersimpan HURUF BESAR semua → jadikan kapital di
        // awal kata tanpa merusak gelar yang sudah ditulis benar.
        if ($name === mb_strtoupper($name)) {
            $name = Str::title(Str::lower($name));
        }

        return preg_replace_callback(
            '/(^|[\s\-])(\p{Ll})/u',
            fn ($m) => $m[1].mb_strtoupper($m[2]),
            $name
        ) ?? $name;
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
