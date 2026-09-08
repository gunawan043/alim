<?php

declare(strict_types=1);

namespace App\Authorization\Support;

use App\Authorization\DTO\PermissionOrigin;

final class PermissionTreeNormalizer
{
    public static function normalize(array $origins): array
    {
        $filtered = array_filter(
            $origins,
            static fn ($origin): bool => $origin instanceof PermissionOrigin
        );

        $indexed = array_values($filtered);

        usort(
            $indexed,
            static function (PermissionOrigin $a, PermissionOrigin $b): int {
                $cmp = strcmp((string) $a->scope, (string) $b->scope);
                if ($cmp !== 0) {
                    return $cmp;
                }
                $cmp = strcmp($a->permission, $b->permission);
                if ($cmp !== 0) {
                    return $cmp;
                }

                return strcmp($a->provider, $b->provider);
            }
        );

        return $indexed;
    }
}
