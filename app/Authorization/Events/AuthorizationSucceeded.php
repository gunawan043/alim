<?php

declare(strict_types=1);

namespace App\Authorization\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AuthorizationSucceeded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int|string $userId,
        public readonly string $permission,
        public readonly string $scopeKey,
    ) {}
}