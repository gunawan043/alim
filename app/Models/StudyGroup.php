<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StudyGroup extends Model
{
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
        'capacity'  => 'integer',
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
        return $this->belongsTo(\App\Models\User::class, 'homeroom_teacher_id');
    }

    public function studentClassHistories(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return $this->gradeLevel
            ? "{$this->gradeLevel->name} {$this->name}"
            : $this->name;
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($q) { return $q->where('is_active', true); }
}
