<?php

declare(strict_types=1);

namespace App\Authorization\Support;

use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;

final class RevocationResolver
{
    /**
     * Apply DENY > ALLOW precedence:
     *   - Any non-REVOCATION origin whose permission also has a REVOCATION origin is removed.
     *   - REVOCATION origins are preserved for audit trail.
     *
     * @param array<int, PermissionOrigin> $origins
     * @return array<int, PermissionOrigin>
     */
    public static function resolve(array $origins): array
    {
        // Build deny map keyed by (permission_name, scope_key)
        $denied = [];

        foreach ($origins as $origin) {
            if ($origin->source === PermissionSource::REVOCATION) {
                $denied[$origin->permission . '::' . $origin->scope->value] = true;
            }
        }

        if (empty($denied)) {
            return $origins;
        }

        $kept = [];

        foreach ($origins as $origin) {
            $denyKey = $origin->permission . '::' . $origin->scope->value;
            if ($origin->source !== PermissionSource::REVOCATION && isset($denied[$denyKey])) {
                continue;
            }
            $kept[] = $origin;
        }

        return array_values($kept);
    }
}