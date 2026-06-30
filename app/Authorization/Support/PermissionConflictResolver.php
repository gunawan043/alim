<?php

declare(strict_types=1);

namespace App\Authorization\Support;

use App\Authorization\DTO\PermissionOrigin;

final class PermissionConflictResolver
{
    /**
     * Final duplicate-elimination pass.
     * Preserves deterministic ordering: (permission asc, scope asc, provider asc).
     *
     * @param array<int, PermissionOrigin> $origins
     * @return array<int, PermissionOrigin>
     */
    public static function resolve(array $origins): array
    {
        $unique = [];

        foreach ($origins as $origin) {
            $key = implode("\0", [
                $origin->permission,
                (string) $origin->scope,
                $origin->provider,
                $origin->source->value,
            ]);

            if (!isset($unique[$key])) {
                $unique[$key] = $origin;
            }
        }

        $result = array_values($unique);

        usort(
            $result,
            static function (PermissionOrigin $a, PermissionOrigin $b): int {
                $cmp = strcmp($a->permission, $b->permission);
                if ($cmp !== 0) {
                    return $cmp;
                }
                $cmp = strcmp((string) $a->scope, (string) $b->scope);
                if ($cmp !== 0) {
                    return $cmp;
                }
                return strcmp($a->provider, $b->provider);
            }
        );

        return $result;
    }
}