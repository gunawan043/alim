<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Immutable history of a GTK work-unit placement period.
 *
 * The current state remains in gtk_work_unit. This model records each
 * start/end cycle so transfer and re-placement do not lose history.
 */
class GtkWorkUnitPeriod extends Model
{
    public const STATUS_PLANNED = 'planned';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'gtk_work_unit_periods';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'placement_id',
        'user_id',
        'work_unit_id',
        'position_id',
        'decree_id',
        'status',
        'start_date',
        'end_date',
        'ended_reason',
        'ended_by',
        'created_by',
    ];

    protected $casts = [
        'id' => 'string',
        'placement_id' => 'string',
        'user_id' => 'string',
        'work_unit_id' => 'string',
        'position_id' => 'string',
        'decree_id' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'ended_by' => 'string',
        'created_by' => 'string',
    ];

    public function placement(): BelongsTo
    {
        return $this->belongsTo(GtkWorkUnit::class, 'placement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class, 'work_unit_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(StructuralPosition::class, 'position_id');
    }

    public function decree(): BelongsTo
    {
        return $this->belongsTo(InstitutionDecree::class, 'decree_id');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNull('end_date');
    }

    public function scopeEffectiveOn($query, $date)
    {
        $date = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : (string) $date;

        return $query->where('status', self::STATUS_ACTIVE)
            ->whereDate('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
            });
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForWorkUnit($query, string $workUnitId)
    {
        return $query->where('work_unit_id', $workUnitId);
    }

    public function getIsEffectiveAttribute(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $today = now()->toDateString();

        if ($this->start_date && $this->start_date->toDateString() > $today) {
            return false;
        }

        return $this->end_date === null || $this->end_date->toDateString() >= $today;
    }
}
