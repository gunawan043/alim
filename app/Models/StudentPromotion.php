<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StudentPromotion extends Model
{
    protected $table = 'student_promotions';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'from_academic_year_id',
        'to_academic_year_id',
        'from_study_group_id',
        'to_study_group_id',
        'promotion_date',
        'status',
        'auto_enroll',
        'include_inactive',
        'skip_graduate',
        'grade_shift',
        'executed_by',
        'executed_at',
        'notes',
    ];

    protected $casts = [
        'promotion_date'  => 'date',
        'executed_at'     => 'datetime',
        'auto_enroll'     => 'boolean',
        'include_inactive' => 'boolean',
        'skip_graduate'   => 'boolean',
        'grade_shift'     => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function fromAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'from_academic_year_id');
    }

    public function toAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'to_academic_year_id');
    }

    public function fromStudyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'from_study_group_id');
    }

    public function toStudyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'to_study_group_id');
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(StudentPromotionDetail::class, 'promotion_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeDraft($q)
    {
        return $q->where('status', 'draft');
    }

    public function scopeProcessed($q)
    {
        return $q->where('status', 'processed');
    }

    public function scopeCompleted($q)
    {
        return $q->where('status', 'completed');
    }

    public function scopeByAcademicYear($q, $yearId)
    {
        return $q->where('from_academic_year_id', $yearId);
    }

    // ── Accessors ────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'Draft',
            'processed' => 'Diproses',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default     => ucfirst($this->status ?? ''),
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'secondary',
            'processed' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            default     => 'secondary',
        };
    }
}