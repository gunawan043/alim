<?php

declare(strict_types=1);

namespace App\Authorization\Support;

use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;

final readonly class PermissionMergeResolver
{
    public function __construct(
        private int $delegationPriority = 100,
        private int $assignmentPriority = 50,
        private int $employmentPriority = 10,
        private int $manualPriority = 25,
    ) {}

    /**
     * @param  array<int, PermissionOrigin>  $origins
     * @return array<int, PermissionOrigin>
     */
    public function resolve(array $origins): array
    {
        // Step 1: Expand wildcard/star permissions (MERGE rule).
        //   e.g., students.* expands to all registered students.* permissions.
        $expandedOrigins = $this->expandWildcards($origins);

        // Step 2: Build deny-map from REVOCATION origins.
        $denied = [];
        $filtered = [];

        foreach ($expandedOrigins as $origin) {
            if ($origin->source === PermissionSource::REVOCATION) {
                $denied[$origin->permission.'::'.$origin->scope->value] = true;

                continue;
            }
            $filtered[] = $origin;
        }

        // Step 3: Priority-based conflict resolution (DENY > ALLOW).
        $byPriority = [];

        foreach ($filtered as $origin) {
            $priority = $this->priorityFor($origin->source);
            $permission = $origin->permission;
            $scopeKey = $origin->scope->value;
            $denyKey = $permission.'::'.$scopeKey;

            // Skip if revoked
            if (isset($denied[$denyKey])) {
                continue;
            }

            if (! isset($byPriority[$denyKey]) || $byPriority[$denyKey]['priority'] < $priority) {
                $byPriority[$denyKey] = [
                    'priority' => $priority,
                    'origin' => $origin,
                ];
            }
        }

        $resolved = array_values(array_map(
            static fn (array $entry): PermissionOrigin => $entry['origin'],
            $byPriority
        ));

        usort(
            $resolved,
            static function (PermissionOrigin $a, PermissionOrigin $b): int {
                $cmp = strcmp($a->permission, $b->permission);

                return $cmp !== 0 ? $cmp : strcmp((string) $a->scope, (string) $b->scope);
            }
        );

        return $resolved;
    }

    /**
     * Expand wildcard permissions (e.g., students.*) to their registered leaf permissions.
     *
     * @param  array<int, PermissionOrigin>  $origins
     * @return array<int, PermissionOrigin>
     */
    private function expandWildcards(array $origins): array
    {
        $registry = \App\Authorization\Registry\PermissionRegistry::all();
        $expanded = [];

        foreach ($origins as $origin) {
            if (str_ends_with($origin->permission, '.*')) {
                $prefix = rtrim($origin->permission, '.*');
                foreach ($registry as $perm => $desc) {
                    if ($perm === $origin->permission || str_starts_with($perm, $prefix)) {
                        $expanded[] = $this->cloneWithPermission($origin, $perm);
                    }
                }
            } else {
                $expanded[] = $origin;
            }
        }

        return $expanded;
    }

    private function cloneWithPermission(PermissionOrigin $origin, string $permission): PermissionOrigin
    {
        return new PermissionOrigin(
            provider: $origin->provider,
            permission: $permission,
            reason: $origin->reason,
            scope: $origin->scope,
            source: $origin->source,
        );
    }

    private function priorityFor(PermissionSource $source): int
    {
        return match ($source) {
            PermissionSource::DELEGATION => $this->delegationPriority,
            PermissionSource::ASSIGNMENT => $this->assignmentPriority,
            PermissionSource::MANUAL => $this->manualPriority,
            PermissionSource::EMPLOYMENT => $this->employmentPriority,
            PermissionSource::REVOCATION => PHP_INT_MIN,
        };
    }
}
