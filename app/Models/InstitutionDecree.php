<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class InstitutionDecree extends Model
{
    use LogsDeletion;

    protected $table = 'institution_decrees';

    protected $keyType = 'string';

    public $incrementing = false;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    public const WORKFLOW_STATES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_PENDING_REVIEW,
        self::STATUS_REVIEWED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_ACTIVE,
        self::STATUS_ARCHIVED,
    ];

    protected $casts = [
        'issued_date' => 'date',
        'effective_date' => 'date',
        'end_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'decree_number',
        'decree_type',
        'title',
        'description',
        'academic_year_id',
        'school_id',
        'issued_date',
        'effective_date',
        'end_date',
        'signed_by',
        'signed_position',
        'document_path',
        'document_filename',
        'status',
        'submitted_at',
        'submitted_by',
        'approved_at',
        'approved_by',
        'rejection_reason',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'decree_id');
    }

    public function additionalTasks(): HasMany
    {
        return $this->hasMany(GtkAdditionalTask::class, 'decree_id');
    }

    public function scopePendingReview($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_REVIEWED,
        ]);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isInWorkflow(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_REVIEWED,
        ], true);
    }

    public function isActionable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_REVIEWED,
            self::STATUS_APPROVED,
        ], true);
    }
}
