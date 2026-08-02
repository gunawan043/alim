<?php

declare(strict_types=1);

namespace App\Authorization\Services;

use App\Authorization\Contracts\PermissionCacheManager;
use App\Authorization\Contracts\SnapshotRepository;
use App\Authorization\Contracts\SnapshotResolver as SnapshotResolverContract;
use App\Authorization\DTO\PermissionBag;
use App\Authorization\Events\SnapshotCacheHit;
use App\Authorization\Events\SnapshotCacheMiss;
use App\Authorization\Events\SnapshotExpired;
use App\Authorization\Events\SnapshotLoaded;
use App\Authorization\ValueObjects\OrganizationContext;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final class SnapshotResolver implements SnapshotResolverContract
{
    public function __construct(
        private readonly PermissionCacheManager $cache,
        private readonly SnapshotRepository $repository,
        private readonly SnapshotRebuildService $rebuildService,
        private readonly Dispatcher $events,
        private readonly bool $emitEvents = true,
        private readonly int $snapshotTtl = 3600,
    ) {}

    public function resolve(Model $subject, OrganizationContext $context): ?PermissionBag
    {
        $userId = (string) $subject->getKey();
        $scopeKey = (string) $context->toScopeKey();

        $bag = $this->cache->get($userId, $scopeKey);
        if ($bag !== null) {
            if ($this->emitEvents) {
                $this->events->dispatch(new SnapshotCacheHit($bag, $userId, $scopeKey));
                $this->events->dispatch(new SnapshotLoaded($bag, $userId, $scopeKey, 'cache'));
            }

            return $bag;
        }

        if ($this->emitEvents) {
            $this->events->dispatch(new SnapshotCacheMiss($userId, $scopeKey));
        }

        $stored = $this->repository->findByScopeKey($scopeKey, $userId);

        if ($stored !== null) {
            $this->cache->put($stored, $userId, $scopeKey);

            if ($this->isExpired($stored)) {
                if ($this->emitEvents) {
                    $this->events->dispatch(new SnapshotExpired($userId, $scopeKey));
                }

                if ($this->subjectIsUser($subject)) {
                    $rebuilt = $this->rebuildService->rebuild($subject, $context, 'expired');
                    if ($this->emitEvents) {
                        $this->events->dispatch(new SnapshotLoaded($rebuilt, $userId, $scopeKey, 'rebuild'));
                    }

                    return $rebuilt;
                }

                return $stored;
            }

            if ($this->emitEvents) {
                $this->events->dispatch(new SnapshotLoaded($stored, $userId, $scopeKey, 'repository'));
            }

            return $stored;
        }

        if ($this->subjectIsUser($subject)) {
            try {
                $bag = $this->rebuildService->rebuild($subject, $context, 'cold-start');
                if ($this->emitEvents) {
                    $this->events->dispatch(new SnapshotLoaded($bag, $userId, $scopeKey, 'cold-start'));
                }

                return $bag;
            } catch (Throwable) {
                // Fail-closed: return null when rebuild fails.
                return null;
            }
        }

        return null;
    }

    public function resolveOrFail(Model $subject, OrganizationContext $context): PermissionBag
    {
        $bag = $this->resolve($subject, $context);

        if ($bag !== null) {
            return $bag;
        }

        if (! $this->subjectIsUser($subject)) {
            throw new \RuntimeException('Snapshot resolution failed for non-user subject.');
        }

        return $this->rebuildService->rebuild($subject, $context, 'force');
    }

    private function isExpired(PermissionBag $bag): bool
    {
        if ($this->snapshotTtl <= 0) {
            return false;
        }

        $createdAt = $bag->getMetadata()->createdAt;

        try {
            $expiresAt = $createdAt->modify("+{$this->snapshotTtl} seconds");

            return new \DateTimeImmutable >= $expiresAt;
        } catch (Throwable) {
            return false;
        }
    }

    private function subjectIsUser(Model $subject): bool
    {
        return $subject instanceof \App\Models\User;
    }
}
