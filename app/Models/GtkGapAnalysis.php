<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GtkGapAnalysis extends Model
{
    protected $table = 'gtk_gap_analyses';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'subject_id',
        'study_group_id',
        'grade_level_id',
        'mapel_name',
        'mapel_category',
        'rombel_count',
        'required_teaching_slots',
        'actual_teaching_slots',
        'covering_teachers',
        'deficit_slots',
        'gap_status',
        'recommendation',
    ];

    protected $casts = [
        'rombel_count' => 'integer',
        'required_teaching_slots' => 'integer',
        'actual_teaching_slots' => 'integer',
        'covering_teachers' => 'integer',
        'deficit_slots' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function getGapStatusLabelAttribute(): string
    {
        return match ($this->gap_status) {
            'satisfied' => 'Terpenuhi',
            'partial' => 'Sebagian',
            'critical_missing' => 'Kekurangan Kritis',
            'no_teacher' => 'Tanpa Guru',
            default => ucfirst($this->gap_status),
        };
    }

    public function getGapStatusBadgeColorAttribute(): string
    {
        return match ($this->gap_status) {
            'satisfied' => 'success',
            'partial' => 'warning',
            'critical_missing' => 'danger',
            'no_teacher' => 'dark',
            default => 'secondary',
        };
    }

    /**
     * Scope: critical gaps only (deficit > 0)
     */
    public function scopeWithDeficit($query)
    {
        return $query->where('deficit_slots', '>', 0);
    }

    /**
     * Scope: gap without any teacher
     */
    public function scopeNoTeacher($query)
    {
        return $query->where('covering_teachers', 0);
    }
}
