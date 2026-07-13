<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationEventLog extends Model
{
    protected $table = 'integration_event_log';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RETRYING = 'retrying';

    protected $fillable = [
        'event_name',
        'aggregate_id',
        'aggregate_type',
        'source_module',
        'target_module',
        'status',
        'attempt',
        'payload',
        'error',
        'dispatched_by',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public static function record(
        string $eventName,
        string $sourceModule,
        ?string $targetModule,
        ?string $aggregateId,
        ?string $aggregateType,
        array $payload,
        string $status = self::STATUS_PROCESSED,
        ?string $error = null,
        ?string $dispatchedBy = null,
    ): self {
        return self::create([
            'event_name' => $eventName,
            'source_module' => $sourceModule,
            'target_module' => $targetModule,
            'aggregate_id' => $aggregateId,
            'aggregate_type' => $aggregateType,
            'payload' => $payload,
            'status' => $status,
            'error' => $error,
            'dispatched_by' => $dispatchedBy,
            'processed_at' => $status === self::STATUS_PROCESSED ? now() : null,
        ]);
    }
}