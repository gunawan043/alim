<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ChainStep extends Model
{
    use HasUuid;

    protected $table = 'chain_steps';

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'id',
        'chain_id',
        'position',
        'name',
        'handler',
        'status',
        'attempts',
        'max_attempts',
        'payload',
        'result',
        'error_message',
        'started_at',
        'completed_at',
        'duration_ms',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_ms' => 'integer',
        'position' => 'integer',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
    ];

    public function chain(): BelongsTo
    {
        return $this->belongsTo(Chain::class, 'chain_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_SKIPPED,
        ], true);
    }

    public function markRunning(): void
    {
        $this->forceFill([
            'status' => self::STATUS_RUNNING,
            'started_at' => Carbon::now(),
        ])->save();
    }

    public function markCompleted(array $result = []): void
    {
        $completedAt = Carbon::now();
        $durationMs = $this->started_at
            ? (int) round($completedAt->diffInMilliseconds($this->started_at))
            : 0;

        $this->forceFill([
            'status' => self::STATUS_COMPLETED,
            'result' => $result,
            'completed_at' => $completedAt,
            'duration_ms' => $durationMs,
        ])->save();
    }

    public function markFailed(string $message, ?\Throwable $exception = null): void
    {
        $completedAt = Carbon::now();
        $durationMs = $this->started_at
            ? (int) round($completedAt->diffInMilliseconds($this->started_at))
            : 0;

        $result = $this->result ?? [];
        $result['error_class'] = $exception ? get_class($exception) : null;

        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'error_message' => $message,
            'result' => $result,
            'completed_at' => $completedAt,
            'duration_ms' => $durationMs,
        ])->save();
    }

    public function markSkipped(string $reason = ''): void
    {
        $completedAt = Carbon::now();
        $durationMs = $this->started_at
            ? (int) round($completedAt->diffInMilliseconds($this->started_at))
            : 0;

        $this->forceFill([
            'status' => self::STATUS_SKIPPED,
            'error_message' => $reason ?: null,
            'completed_at' => $completedAt,
            'duration_ms' => $durationMs,
        ])->save();
    }
}
