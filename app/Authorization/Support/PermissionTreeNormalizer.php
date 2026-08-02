<?php

declare(strict_types=1);

namespace App\Authorization\Support;

final class PermissionTreeNormalizer
{
    public static function normalize(array $origins): array
    {
        $filtered = array_filter(
            $origins,
            static fn ($origin): bool => $origin instanceof \App\Authorization\DTO\PermissionOrigin
        );

        $indexed = array_values($filtered);

        usort(
            $indexed,
            static function (\App\Authorization\DTO\PermissionOrigin $a, \App\Authorization\DTO\PermissionOrigin $b): int {
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
