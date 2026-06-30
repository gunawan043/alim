<?php

declare(strict_types=1);

namespace App\Authorization\Contracts;

use App\Authorization\DTO\PermissionBag;
use App\Authorization\Enums\SnapshotStatus;

interface SnapshotRepository
{
    public function save(PermissionBag $bag, int|string $userId): void;

    public function findByScopeKey(string $scopeKey, int|string $userId): ?PermissionBag;

    /**
     * @return array<int, PermissionBag>
     */
    public function findAllByScopeKey(string $scopeKey): array;

    public function archive(?SnapshotStatus $status = null): void;
}