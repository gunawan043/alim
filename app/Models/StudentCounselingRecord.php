<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentCounselingRecord extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'student_counseling_records';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id',
        'school_id',
        'academic_year_id',
        'counselor_id',
        'session_date',
        'session_number',
        'session_type',
        'topic',
        'description',
        'analysis',
        'follow_up_plan',
        'referral_needed',
        'referred_to',
        'next_session_date',
        'parent_informed',
        'parent_informed_at',
        'parent_informed_by',
        'is_confidential',
        'created_by',
    ];

    protected $casts = [
        'session_date' => 'date',
        'next_session_date' => 'date',
        'parent_informed_at' => 'datetime',
        'referral_needed' => 'boolean',
        'parent_informed' => 'boolean',
        'is_confidential' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function parentInformedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_informed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Accessors ───────────────────────────────────────────────

    public function getSessionTypeTextAttribute(): string
    {
        return match ($this->session_type) {
            'individu' => 'Individu',
            'kelompok' => 'Kelompok',
            'krisis'   => 'Krisis',
            default    => $this->session_type,
        };
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeByStudent($q, $studentId)
    {
        return $q->where('student_id', $studentId);
    }

    public function scopeNeedsReferral($q)
    {
        return $q->where('referral_needed', true);
    }
}
