<?php

declare(strict_types=1);

namespace App\Authorization\Contracts;

use App\Authorization\DTO\PermissionOrigin;

interface PermissionProvider
{
    /**
     * Return permission origins for a given user within a scope context.
     *
     * @return array<int, PermissionOrigin>
     */
    public function provide(int|string $userId): array;
}