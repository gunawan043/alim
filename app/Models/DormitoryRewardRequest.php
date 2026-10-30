<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryRewardRequest extends Model
{
    protected $table = 'dormitory_reward_requests';

    protected $keyType = 'string';

    public $incrementing = false;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_achievement_id',
        'student_id',
        'dormitory_id',
        'academic_year_id',
        'title',
        'description',
        'category',
        'level',
        'point_catalog_id',
        'points',
        'status',
        'notify_approvers',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'cancelled_at',
        'rejection_reason',
        'dormitory_reward_id',
        'source_snapshot',
    ];

    protected $casts = [
        'points' => 'integer',
        'notify_approvers' => 'boolean',
        'reviewed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'source_snapshot' => 'array',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu Persetujuan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_CANCELLED => 'Dibatalkan',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary',
        };
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(StudentAchievement::class, 'student_achievement_id');
    }

    public function pointCatalog(): BelongsTo
    {
        return $this->belongsTo(DormitoryPointCatalog::class, 'point_catalog_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(DormitoryReward::class, 'dormitory_reward_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
