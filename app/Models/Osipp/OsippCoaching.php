<?php

namespace App\Models\Osipp;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OsippCoaching extends OsippModel
{
    use SoftDeletes;

    protected $table = 'osipp_coachings';

    public const TYPE_INDIVIDUAL = 'individu';

    public const TYPE_GROUP = 'kelompok';

    public const TYPE_OPTIONS = [
        self::TYPE_INDIVIDUAL => 'Individu',
        self::TYPE_GROUP => 'Kelompok',
    ];

    public const STATUS_PLANNED = 'planned';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_OPTIONS = [
        self::STATUS_PLANNED => 'Direncanakan',
        self::STATUS_COMPLETED => 'Terlaksana',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    protected $casts = [
        'coaching_date' => 'date',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(OsippPeriod::class, 'period_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(OsippMember::class, 'member_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(OsippCoachingAttendance::class, 'coaching_id');
    }

    public function scopeForPeriod($query, string $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'warning',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_OPTIONS[$this->coaching_type] ?? ucfirst((string) $this->coaching_type);
    }
}
