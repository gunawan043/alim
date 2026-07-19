<?php

namespace App\Policies;

use App\Authorization\ValueObjects\OrganizationContext;
use App\Models\User;

class SarprasWorkspacePolicy
{
    public function __construct(
        private readonly OrganizationContext $context
    ) {}

    public function viewAll(User $user): bool
    {
        return canUserPermission($user, 'sarpras_all_access')
            || canUserPermission($user, 'inventory_view');
    }

    public function view(User $user, object $resource): bool
    {
        if ($this->viewAll($user)) {
            return true;
        }

        if (! $this->context->hasValidSchool()) {
            return false;
        }

        return ($resource->school_id ?? null) === $this->context->schoolId;
    }

    public function create(User $user): bool
    {
        return canUserPermission($user, 'sarpras_create');
    }

    public function update(User $user, object $resource): bool
    {
        if ($this->viewAll($user)) {
            return true;
        }

        if (! $this->context->hasValidSchool()) {
            return false;
        }

        return ($resource->school_id ?? null) === $this->context->schoolId
            && (($resource->created_by ?? null) === $user->id || canUserPermission($user, 'sarpras_edit'));
    }

    public function delete(User $user, object $resource): bool
    {
        return canUserPermission($user, 'sarpras_delete');
    }
}
