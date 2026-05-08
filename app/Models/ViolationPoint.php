<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ViolationPoint extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id',
        'study_group_id',
        'academic_year_id',
        'recorded_by',
        'violation_date',
        'violation_type',
        'points',
        'description',
        'action_taken',
    ];

    protected $casts = [
        'violation_date' => 'date',
        'points' => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function scopeByAcademicYear($q, $yearId)
    {
        return $q->where('academic_year_id', $yearId);
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeByStudent($q, $studentId)
    {
        return $q->where('student_id', $studentId);
    }

    public function scopeByStudyGroup($q, $studyGroupId)
    {
        return $q->where('study_group_id', $studyGroupId);
    }

    public function scopeByDateRange($q, $start, $end)
    {
        return $q->whereBetween('violation_date', [$start, $end]);
    }
}
