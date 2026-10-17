<?php

declare(strict_types=1);

namespace App\Authorization\Events;

use App\Authorization\DTO\PermissionBag;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SnapshotLoaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PermissionBag $bag,
        public readonly int|string $userId,
        public readonly string $scopeKey,
        public readonly string $source,
    ) {}
}
