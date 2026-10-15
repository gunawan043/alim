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
        'school_id', 'academic_year_id', 'grade_level_id',
        'homeroom_teacher_id', 'name', 'code',
        'capacity', 'room', 'curriculum_type', 'shift',
        'is_active', 'notes',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
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

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
