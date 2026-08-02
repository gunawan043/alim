<?php

declare(strict_types=1);

namespace App\Authorization\Contracts;

use App\Authorization\DTO\PermissionBag;

interface PermissionCacheManager
{
    public function remember(string $userId, string $scopeKey, callable $resolver): PermissionBag;

    public function get(string $userId, string $scopeKey): ?PermissionBag;

    public function put(PermissionBag $bag, string $userId, ?string $scopeKey = null): void;

    public function forget(string $userId, string $scopeKey): void;

    public function forgetUser(int|string $userId): void;

    public function forgetScope(string $scopeKey): void;

    /**
     * @param  array<int|string>  $userIds
     */
    public function warm(array $userIds): int;

    public function isTaggable(): bool;
}
