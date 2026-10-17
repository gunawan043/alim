<?php

declare(strict_types=1);

namespace App\Authorization\Listeners;

use App\Authorization\Contracts\PermissionCacheManager;
use App\Authorization\Events\PermissionCacheInvalidated;
use Illuminate\Log\LogManager;

final class PermissionCacheInvalidationListener
{
    public function __construct(
        private readonly PermissionCacheManager $cacheManager,
        private readonly LogManager $log,
    ) {}

    public function handle(PermissionCacheInvalidated $event): void
    {
        $userId = (int) $event->userId;

        if ($userId <= 0) {
            return;
        }

        try {
            $this->cacheManager->forgetUser($userId);

            if ($this->cacheManager->isTaggable() === false) {
                $this->cacheManager->forget((string) $userId, $event->scopeKey);
            }

            $this->log->info('authorization.cache.invalidated', [
                'user_id' => $userId,
                'scope_key' => $event->scopeKey,
                'reason' => $event->reason,
            ]);
        } catch (\Throwable $e) {
            $this->log->error('authorization.cache.invalidation_failed', [
                'user_id' => $userId,
                'scope' => $event->scopeKey,
                'reason' => $event->reason,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
