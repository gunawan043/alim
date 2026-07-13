<?php

declare(strict_types=1);

namespace App\Authorization\Services;

use App\Authorization\Contracts\PermissionBuilder;
use App\Authorization\Contracts\SnapshotRepository;
use App\Authorization\DTO\PermissionBag;
use App\Authorization\Enums\SnapshotStatus;
use App\Authorization\Events\PermissionCacheInvalidated;
use App\Authorization\Events\SnapshotArchived;
use App\Authorization\Events\SnapshotCreated;
use App\Authorization\Exceptions\AuthorizationException;
use App\Authorization\Models\PermissionSnapshot;
use App\Authorization\ValueObjects\OrganizationContext;
use App\Models\User;
use Illuminate\Contracts\Events\Dispatcher;
use Throwable;

final class SnapshotRebuildService
{
    public function __construct(
        private readonly PermissionBuilder $builder,
        private readonly SnapshotRepository $repository,
        private readonly Dispatcher $events,
        private readonly ?\App\Authorization\Contracts\PermissionCacheManager $cache = null,
    ) {}

    public function rebuild(User $user, OrganizationContext $context, string $trigger = 'manual'): PermissionBag
    {
        $userId = $user->getKey();
        $scopeKey = $context->toScopeKey()->__toString();

        try {
            $bag = $this->builder->build($user, $context);

            $existing = $this->repository->findByScopeKey($scopeKey, $userId);

            if ($existing !== null && $existing->getFingerprint() === $bag->getFingerprint()) {
                if ($this->cache !== null) {
                    $this->cache->put($existing, $userId, $scopeKey);
                }
                return $existing;
            }

            $this->repository->save($bag, $userId, $context);

            $this->events->dispatch(new PermissionCacheInvalidated(
                userId: $userId,
                scopeKey: $scopeKey,
                reason: $trigger,
            ));

            if ($this->cache !== null) {
                $this->cache->put($bag, $userId, $scopeKey);
            }

            $this->events->dispatch(new SnapshotCreated(
                bag: $bag,
                userId: $userId,
                trigger: $trigger,
            ));

            return $bag;
        } catch (Throwable $e) {
            $this->writeFailureAudit($userId, $scopeKey, $trigger, $e);

            throw new AuthorizationException(
                'Snapshot rebuild failed.',
                previous: $e,
            );
        }
    }

    public function archiveAll(?SnapshotStatus $status = null): void
    {
        $countBefore = PermissionSnapshot::query()
            ->where('is_current', true)
            ->when($status !== null, function ($q) use ($status) {
                return $q; // note: snapshot.status is in DTO, not model column
            })
            ->count();

        $this->repository->archive($status);

        $this->events->dispatch(new SnapshotArchived(
            scopeKey: '*',
            userId: '*',
            archivedCount: $countBefore,
            filterStatus: $status,
        ));
    }

    private function writeFailureAudit(
        int|string $userId,
        string $scopeKey,
        string $trigger,
        Throwable $e,
    ): void {
        try {
            \App\Authorization\Models\SnapshotAuditLog::query()->create([
                'user_id'      => $userId,
                'scope_key'    => $scopeKey,
                'event'        => $trigger,
                'fingerprint'  => str_pad('', 64, '0'),
                'status'       => 'failed',
                'error'        => $e->getMessage(),
                'created_at'   => now(),
            ]);
        } catch (Throwable) {
            // Swallow — primary failure already thrown.
        }
    }
}