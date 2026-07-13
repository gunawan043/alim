<?php

declare(strict_types=1);

namespace App\Authorization\Services;

use App\Authorization\ValueObjects\OrganizationContext;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Filter users by snapshot-derived permissions rather than role names.
 *
 * Replaces the legacy User::role([...]) and whereHas('roles', ...) patterns
 * with permission-driven lookups.
 *
 * A user matches a permission if their snapshot (computed for the given
 * context) contains that permission. The snapshot is resolved through
 * the standard runtime — no role names are involved.
 */
final class UserFilterService
{
    public function __construct(
        private readonly SnapshotRebuildService $rebuildService,
    ) {}

    /**
     * Return user IDs whose snapshot contains the given permission.
     *
     * @param string $permission e.g. 'gtk.teacher.assignable'
     * @return array<int, string>
     */
    public function userIdsWithPermission(string $permission, OrganizationContext $context): array
    {
        $snapshotModel = new \App\Authorization\Models\PermissionSnapshot();
        $snapshotTable = $snapshotModel->getTable();

        // The permissions column is JSON-cast array, so we can use whereJsonContains
        // or fall back to whereRaw for databases that don't support it natively.
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $rows = DB::table($snapshotTable)
                ->where('scope_key', (string) $context->toScopeKey())
                ->whereRaw("JSON_CONTAINS(permissions, ?, '$')", [$permission])
                ->whereNull('archived_at')
                ->pluck('user_id')
                ->all();
        } else {
            $rows = DB::table($snapshotTable)
                ->where('scope_key', (string) $context->toScopeKey())
                ->whereJsonContains('permissions', $permission)
                ->whereNull('archived_at')
                ->pluck('user_id')
                ->all();
        }

        return array_values(array_unique(array_map('strval', $rows)));
    }

    /**
     * Return users whose snapshot contains the given permission.
     *
     * @return Collection<int, User>
     */
    public function usersWithPermission(string $permission, OrganizationContext $context): Collection
    {
        $ids = $this->userIdsWithPermission($permission, $context);
        if ($ids === []) {
            return User::query()->whereRaw('0 = 1')->get();
        }
        return User::query()->whereIn('id', $ids)->get();
    }

    /**
     * Apply the permission filter to an existing Eloquent query.
     * Returns the same builder with a whereIn applied.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function applyToQuery($query, string $permission, OrganizationContext $context): void
    {
        $ids = $this->userIdsWithPermission($permission, $context);
        if ($ids === []) {
            $query->whereRaw('0 = 1');
            return;
        }
        $query->whereIn('users.id', $ids);
    }

    /**
     * Return users who do NOT have the given permission.
     * Inverse filter for "exclude super-admin" patterns.
     *
     * @return Collection<int, User>
     */
    public function usersWithoutPermission(string $permission, OrganizationContext $context): Collection
    {
        $ids = $this->userIdsWithPermission($permission, $context);
        $query = User::query();
        if ($ids === []) {
            return $query->get();
        }
        return $query->whereNotIn('users.id', $ids)->get();
    }

    /**
     * Apply the inverse permission filter to a query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function applyInverseToQuery($query, string $permission, OrganizationContext $context): void
    {
        $ids = $this->userIdsWithPermission($permission, $context);
        if ($ids === []) {
            return;
        }
        $query->whereNotIn('users.id', $ids);
    }
}