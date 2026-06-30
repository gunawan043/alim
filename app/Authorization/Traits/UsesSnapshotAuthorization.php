<?php

declare(strict_types=1);

namespace App\Authorization\Traits;

use App\Authorization\Services\AuthorizationManager;
use App\Authorization\ValueObjects\OrganizationContext;
use App\Models\User;

/**
 * Use inside Policy classes to short-circuit authorization checks
 * against the snapshot-based AuthorizationManager.
 *
 * Example:
 *
 *   class StudentPolicy
 *   {
 *       use UsesSnapshotAuthorization;
 *
 *       public function view(User $user, Student $student): bool
 *       {
 *           return $this->authorizePermission($user, 'students.view');
 *       }
 *   }
 *
 * Policies using this trait MUST be invoked inside a request that has
 * an OrganizationContext bound; otherwise the trait returns false
 * (fail-closed).
 */
trait UsesSnapshotAuthorization
{
    protected function authorizePermission(
        User $user,
        string $permission,
    ): bool {
        if (! app()->bound(OrganizationContext::class)) {
            return false;
        }

        $context = app(OrganizationContext::class);
        if (! $context instanceof OrganizationContext) {
            return false;
        }

        return app(AuthorizationManager::class)->allows($user, $permission, $context);
    }
}
