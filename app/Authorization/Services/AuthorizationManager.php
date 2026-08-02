<?php

declare(strict_types=1);

namespace App\Authorization\Services;

use App\Authorization\Contracts\SnapshotResolver;
use App\Authorization\DTO\PermissionBag;
use App\Authorization\Events\AuthorizationDenied;
use App\Authorization\Events\AuthorizationSucceeded;
use App\Authorization\ValueObjects\OrganizationContext;
use App\Models\User;
use Illuminate\Contracts\Events\Dispatcher;

final class AuthorizationManager
{
    public function __construct(
        private readonly SnapshotResolver $resolver,
        private readonly Dispatcher $events,
        private readonly bool $emitEvents,
    ) {}

    public function allows(User $user, string $permission, OrganizationContext $context): bool
    {
        if ($user->isSystemAdmin()) {
            return $this->succeedAndDispatch($user, $permission, $context);
        }

        $bag = $this->resolveBag($user, $context);
        if ($bag === null) {
            return $this->denyAndDispatch($user, $permission, $context, 'no-snapshot');
        }

        if (in_array($permission, $bag->getPermissions(), true)) {
            return $this->succeedAndDispatch($user, $permission, $context);
        }

        return $this->denyAndDispatch($user, $permission, $context, 'permission-not-in-snapshot');
    }

    public function denies(User $user, string $permission, OrganizationContext $context): bool
    {
        return ! $this->allows($user, $permission, $context);
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<string, bool> Map permission => bool
     */
    public function checkMany(User $user, array $permissions, OrganizationContext $context): array
    {
        if ($user->isSystemAdmin()) {
            $result = [];
            foreach ($permissions as $permission) {
                $result[$permission] = true;
                if ($this->emitEvents) {
                    $this->events->dispatch(new AuthorizationSucceeded(
                        userId: (string) $user->getKey(),
                        permission: $permission,
                        scopeKey: (string) $context->toScopeKey(),
                    ));
                }
            }

            return $result;
        }

        $bag = $this->resolveBag($user, $context);
        $result = [];

        foreach ($permissions as $permission) {
            if ($bag === null) {
                $result[$permission] = false;
                if ($this->emitEvents) {
                    $this->events->dispatch(new AuthorizationDenied(
                        userId: (string) $user->getKey(),
                        permission: $permission,
                        scopeKey: (string) $context->toScopeKey(),
                        reason: 'no-snapshot',
                    ));
                }

                continue;
            }

            $granted = isset($bag->getPermissions()[$permission]);
            $result[$permission] = $granted;

            if ($granted) {
                if ($this->emitEvents) {
                    $this->events->dispatch(new AuthorizationSucceeded(
                        userId: (string) $user->getKey(),
                        permission: $permission,
                        scopeKey: (string) $context->toScopeKey(),
                    ));
                }
            } else {
                if ($this->emitEvents) {
                    $this->events->dispatch(new AuthorizationDenied(
                        userId: (string) $user->getKey(),
                        permission: $permission,
                        scopeKey: (string) $context->toScopeKey(),
                        reason: 'permission-not-in-snapshot',
                    ));
                }
            }
        }

        return $result;
    }

    public function getSnapshot(User $user, OrganizationContext $context): ?PermissionBag
    {
        return $this->resolver->resolve($user, $context);
    }

    private function resolveBag(User $user, OrganizationContext $context): ?PermissionBag
    {
        return $this->resolver->resolve($user, $context);
    }

    private function succeedAndDispatch(User $user, string $permission, OrganizationContext $context): bool
    {
        if ($this->emitEvents) {
            $this->events->dispatch(new AuthorizationSucceeded(
                userId: (string) $user->getKey(),
                permission: $permission,
                scopeKey: (string) $context->toScopeKey(),
            ));
        }

        return true;
    }

    private function denyAndDispatch(User $user, string $permission, OrganizationContext $context, string $reason): bool
    {
        if ($this->emitEvents) {
            $this->events->dispatch(new AuthorizationDenied(
                userId: (string) $user->getKey(),
                permission: $permission,
                scopeKey: (string) $context->toScopeKey(),
                reason: $reason,
            ));
        }

        return false;
    }
}
