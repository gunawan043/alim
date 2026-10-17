<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GtkGapSummary extends Model
{
    public const STATUS_DEFICIT = 'deficit';

    public const STATUS_SURPLUS = 'surplus';

    public const STATUS_BALANCED = 'balanced';

    public const STATUS_UNCOVERED = 'uncovered';

    public const DIMENSION_SUBJECT = 'subject';

    public const DIMENSION_TEACHER = 'teacher';

    public const DIMENSION_STUDY_GROUP = 'study_group';

    public const DIMENSION_GRADE_LEVEL = 'grade_level';

    protected $table = 'gtk_gap_summaries';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'analysis_run_id',
        'school_id',
        'academic_year_id',
        'subject_id',
        'study_group_id',
        'teacher_id',
        'dimension',
        'dimension_label',
        'hours_needed',
        'hours_available',
        'hours_gap',
        'teacher_count',
        'assignment_count',
        'group_count',
        'status',
        'ideal_min_hours',
        'ideal_max_hours',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'hours_needed' => 'decimal:2',
        'hours_available' => 'decimal:2',
        'hours_gap' => 'decimal:2',
        'ideal_min_hours' => 'decimal:2',
        'ideal_max_hours' => 'decimal:2',
        'teacher_count' => 'integer',
        'assignment_count' => 'integer',
        'group_count' => 'integer',
    ];

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(GtkAnalysisRun::class, 'analysis_run_id');
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

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
