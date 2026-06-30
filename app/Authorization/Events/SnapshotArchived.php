<?php

declare(strict_types=1);

namespace App\Authorization\Events;

use App\Authorization\Enums\SnapshotStatus;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SnapshotArchived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $scopeKey,
        public readonly int|string $userId,
        public readonly int $archivedCount,
        public readonly ?SnapshotStatus $filterStatus,
    ) {}
}