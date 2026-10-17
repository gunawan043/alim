<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class Chain extends Model
{
    use HasUuid;

    protected $table = 'chains';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'id',
        'root_event',
        'aggregate_type',
        'aggregate_id',
        'school_id',
        'academic_year_id',
        'status',
        'context',
        'total_steps',
        'completed_steps',
        'failed_steps',
        'skipped_steps',
        'triggered_at',
        'completed_at',
    ];

    protected $casts = [
        'context' => 'array',
        'triggered_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_steps' => 'integer',
        'completed_steps' => 'integer',
        'failed_steps' => 'integer',
        'skipped_steps' => 'integer',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(ChainStep::class, 'chain_id')->orderBy('position');
    }

    public function aggregate(): MorphTo
    {
        return $this->morphTo();
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ], true);
    }

    public function progressPercent(): float
    {
        if ($this->total_steps <= 0) {
            return 0.0;
        }

        return round((($this->completed_steps + $this->skipped_steps) / $this->total_steps) * 100, 2);
    }

    public function durationSeconds(): ?int
    {
        if (! $this->triggered_at) {
            return null;
        }

        $end = $this->completed_at ?? Carbon::now();

        return $end->diffInSeconds($this->triggered_at);
    }
}
