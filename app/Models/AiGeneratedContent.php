<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Draft hasil AI untuk RPM/soal. Disimpan terpisah dari modul resmi sampai
 * ditinjau/disetujui, agar tidak mengotori Bank Soal/RPM.
 */
class AiGeneratedContent extends Model
{
    protected $table = 'ai_generated_contents';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'curriculum_profile_id',
        'semester',
        'bahasa',
        'type',
        'subject_id',
        'grade_level_id',
        'title',
        'topik',
        'content',
        'raw_content',
        'status',
        'provider',
        'model',
        'created_by',
        'approved_by',
        'approved_at',
        'saved_bank_id',
        'saved_perangkat_id',
        'saved_at',
    ];

    protected $casts = [
        'content' => 'array',
        'approved_at' => 'datetime',
        'saved_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function curriculumProfile(): BelongsTo
    {
        return $this->belongsTo(CurriculumProfile::class, 'curriculum_profile_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
