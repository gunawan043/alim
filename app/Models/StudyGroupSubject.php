<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class StudyGroupSubject extends Model
{
    use SoftDeletes;

    protected $table = 'study_group_subjects';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (! $m->id) {
                $m->id = (string) Str::uuid();
            }
            // Auto-fill school_id and academic_year_id from study_group if not provided.
            // This guarantees the row is always self-sufficient for downstream cascades.
            if (! $m->school_id && $m->study_group_id) {
                $sg = StudyGroup::find($m->study_group_id);
                if ($sg) {
                    $m->school_id = $sg->school_id;
                    if (! $m->academic_year_id) {
                        $m->academic_year_id = $sg->academic_year_id;
                    }
                }
            }
        });
    }

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'study_group_id',
        'subject_id',
        'teacher_id',
        'weekly_hours',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'weekly_hours' => 'float',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function adminBooks(): HasMany
    {
        return $this->hasMany(TeacherAdminBook::class, 'study_group_subject_id');
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeForStudyGroup($q, string $studyGroupId)
    {
        return $q->where('study_group_id', $studyGroupId);
    }

    public function scopeForSubject($q, string $subjectId)
    {
        return $q->where('subject_id', $subjectId);
    }

    public function scopeForTeacher($q, string $teacherId)
    {
        return $q->where('teacher_id', $teacherId);
    }

    public function scopeForAcademicYear($q, string $academicYearId)
    {
        return $q->where('academic_year_id', $academicYearId);
    }

    public function scopeForSchool($q, string $schoolId)
    {
        return $q->where('school_id', $schoolId);
    }

    // ── Accessors ────────────────────────────────────────────────

    /**
     * Resolve semester from the related academic year.
     * Returns 'ganjil' or 'genap' (or empty string if unresolved).
     */
    public function getSemesterAttribute(): string
    {
        if ($this->academicYear) {
            return (string) $this->academicYear->semester;
        }
        if ($this->academic_year_id) {
            $ay = AcademicYear::find($this->academic_year_id);

            return (string) ($ay?->semester ?? '');
        }

        return '';
    }
}
