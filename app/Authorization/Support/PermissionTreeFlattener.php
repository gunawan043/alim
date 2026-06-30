<?php

declare(strict_types=1);

namespace App\Authorization\Support;

use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;

final class PermissionTreeFlattener
{
    /**
     * @param array<int, PermissionOrigin> $origins
     * @return array<string, PermissionSource>
     */
    public static function toPermissionMap(array $origins): array
    {
        $map = [];

        foreach ($origins as $origin) {
            $map[$origin->permission] = $origin->source;
        }

        return $map;
    }
}