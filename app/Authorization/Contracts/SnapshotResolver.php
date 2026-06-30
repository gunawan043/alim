<?php

declare(strict_types=1);

namespace App\Authorization\Contracts;

use App\Authorization\DTO\PermissionBag;
use App\Authorization\ValueObjects\OrganizationContext;
use Illuminate\Database\Eloquent\Model;

interface SnapshotResolver
{
    /**
     * Resolve a PermissionBag for the given model.
     * Falls back to rebuild if snapshot is expired or missing.
     *
     * @param Model $subject E.g. an instance of App\Models\User
     * @return PermissionBag|null
     */
    public function resolve(Model $subject, OrganizationContext $context): ?PermissionBag;

    /**
     * Force a rebuild regardless of cache / expiration state.
     *
     * @param Model $subject
     * @return PermissionBag
     */
    public function resolveOrFail(Model $subject, OrganizationContext $context): PermissionBag;
}