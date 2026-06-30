<?php

declare(strict_types=1);

namespace App\Authorization\Support;

use App\Authorization\Services\AuthorizationManager;
use App\Authorization\ValueObjects\OrganizationContext;
use Illuminate\Auth\Access\Gate;
use Illuminate\Contracts\Container\BindingResolutionException;

final readonly class AuthorizationGateRegistrar
{
    public function __construct(
        private AuthorizationManager $manager,
    ) {}

    public function register(Gate $gate): void
    {
        $gate::before(function ($user, string $ability) {
            return $this->resolveViaSnapshot($user, $ability);
        });
    }

    /**
     * Decision logic:
     *  - null            -> let Laravel fall through to policies / other Gate::before
     *  - true / false    -> final decision (skip policies)
     *
     * Returns null when OrganizationContext is not bound. That keeps
     * classic Gate behavior intact for endpoints that don't run inside
     * a scope-aware middleware.
     */
    private function resolveViaSnapshot(mixed $user, string $ability): ?bool
    {
        if (! $user instanceof \App\Models\User) {
            return null;
        }

        try {
            $context = app(OrganizationContext::class);
        } catch (BindingResolutionException) {
            return null;
        }

        if (! $context instanceof OrganizationContext) {
            return null;
        }

        return $this->manager->allows($user, $ability, $context);
    }
}