<?php

declare(strict_types=1);

namespace App\Authorization\Repositories;

use App\Authorization\Contracts\SnapshotRepository;
use App\Authorization\DTO\PermissionBag;
use App\Authorization\DTO\SnapshotMetadata;
use App\Authorization\Enums\SnapshotStatus;
use App\Authorization\Models\PermissionSnapshot;
use App\Authorization\ValueObjects\OrganizationContext;
use App\Authorization\ValueObjects\ScopeKey;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Carbon;

final class EloquentSnapshotRepository implements SnapshotRepository
{
    public function save(PermissionBag $bag, int|string $userId, ?OrganizationContext $context = null): void
    {
        $scopeKey = $bag->getMetadata()->scopeKey->__toString();
        $schoolId = $context?->schoolId;

        PermissionSnapshot::query()
            ->where('user_id', $userId)
            ->where('scope_key', $scopeKey)
            ->where('is_current', true)
            ->update([
                'is_current' => false,
                'archived_at' => Carbon::now(),
            ]);

        $snapshot = new PermissionSnapshot;
        $snapshot->user_id = $userId;
        $snapshot->scope_key = $scopeKey;
        $snapshot->scope_school_id = $schoolId;
        $snapshot->fingerprint = $bag->getFingerprint();
        $snapshot->permissions = $bag->getPermissions();
        $snapshot->revoked = $bag->getRevoked();
        $snapshot->expires_at = $bag->getExpiresAt();
        $snapshot->is_current = true;
        $snapshot->save();
    }

    public function findByScopeKey(string $scopeKey, int|string $userId): ?PermissionBag
    {
        $snapshot = PermissionSnapshot::query()
            ->where('user_id', $userId)
            ->where('scope_key', $scopeKey)
            ->where('is_current', true)
            ->first();

        if ($snapshot === null) {
            return null;
        }

        return $this->hydrateBag($snapshot);
    }

    /**
     * @return array<int, PermissionBag>
     */
    public function findAllByScopeKey(string $scopeKey): array
    {
        $snapshots = PermissionSnapshot::query()
            ->where('scope_key', $scopeKey)
            ->where('is_current', true)
            ->get();

        return $snapshots
            ->map(fn (PermissionSnapshot $snapshot): PermissionBag => $this->hydrateBag($snapshot))
            ->all();
    }

    public function archive(?SnapshotStatus $status = null): void
    {
        $query = PermissionSnapshot::query()->where('is_current', true);

        $query->update([
            'is_current' => false,
            'archived_at' => Carbon::now(),
        ]);
    }

    private function hydrateBag(PermissionSnapshot $snapshot): PermissionBag
    {
        $createdAt = $snapshot->created_at;

        $expiresAt = $snapshot->expires_at;

        $metadata = new SnapshotMetadata(
            createdAt: $createdAt instanceof \DateTimeInterface
                ? DateTimeImmutable::createFromInterface($createdAt)
                : new DateTimeImmutable('now', new DateTimeZone('UTC')),
            scopeKey: ScopeKey::fromHash($snapshot->scope_key),
            version: (int) $snapshot->id,
            status: $snapshot->is_current ? SnapshotStatus::ACTIVE : SnapshotStatus::ARCHIVED,
        );

        return new PermissionBag(
            permissions: $snapshot->permissions ?? [],
            revoked: $snapshot->revoked ?? [],
            fingerprint: $snapshot->fingerprint,
            expiresAt: $expiresAt instanceof \DateTimeInterface
                ? DateTimeImmutable::createFromInterface($expiresAt)
                : null,
            metadata: $metadata,
            origins: [],
        );
    }
}
