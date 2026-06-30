<?php

declare(strict_types=1);

namespace App\Authorization\Support;

use App\Authorization\Contracts\PermissionBuilder;
use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionBag;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\DTO\SnapshotFingerprint;
use App\Authorization\DTO\SnapshotMetadata;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\Enums\SnapshotStatus;
use App\Authorization\Models\PermissionSnapshot;
use App\Authorization\Support\PermissionConflictResolver;
use App\Authorization\Support\PermissionMergeResolver;
use App\Authorization\Support\PermissionTreeNormalizer;
use App\Authorization\Support\RevocationResolver;
use App\Authorization\Support\SnapshotFingerprintFactory;
use App\Authorization\Support\SnapshotVersionResolver;
use App\Authorization\ValueObjects\OrganizationContext;
use App\Authorization\ValueObjects\ScopeKey;
use DateTimeImmutable;
use DateTimeZone;

final readonly class EffectivePermissionBuilder implements PermissionBuilder
{
    public function __construct(
        private iterable $providers,
        private PermissionMergeResolver $mergeResolver,
        private RevocationResolver $revocationResolver,
        private PermissionConflictResolver $conflictResolver,
        private SnapshotFingerprintFactory $fingerprintFactory,
        private SnapshotVersionResolver $versionResolver,
        private string $defaultProvider = 'default',
    ) {}

    /**
     * Public facade: build PermissionBag for a user + context.
     */
    public function build(\Illuminate\Database\Eloquent\Model $user, OrganizationContext $context): PermissionBag
    {
        $scopeKey = $context->toScopeKey();

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $this->versionResolver->run(
            $scopeKey,
            $user->{$user->getKeyName()} ?? $user->id,
            function () use ($user, $context, $scopeKey, $now): PermissionBag {
                $allOrigins = $this->collectOrigins($user, $scopeKey);
                $merged = $this->mergeResolver->resolve($allOrigins);
                $deduplicated = PermissionConflictResolver::resolve($merged);
                $finalOrigins = RevocationResolver::resolve($deduplicated);
                $normalised = PermissionTreeNormalizer::normalize($finalOrigins);

                $fingerprint = $this->fingerprintFactory->fromOrigins($normalised, $now);
                $version = $this->versionResolver->nextVersion($scopeKey, $user->{$user->getKeyName()} ?? $user->id);

                $metadata = new SnapshotMetadata(
                    createdAt: $now,
                    scopeKey: $scopeKey,
                    version: $version,
                    status: SnapshotStatus::ACTIVE,
                );

                return new PermissionBag(
                    permissions: [],
                    revoked: [],
                    fingerprint: $fingerprint->hash,
                    expiresAt: null,
                    metadata: $metadata,
                    origins: $normalised,
                );
            }
        );
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\App\Models\User $user
     * @return array<int, PermissionOrigin>
     */
    private function collectOrigins(\Illuminate\Database\Eloquent\Model $user, ScopeKey $scopeKey): array
    {
        $origins = [];

        // Sort providers alphabetically by class name for deterministic fingerprinting.
        $sortedProviders = iterator_to_array($this->providers);
        usort($sortedProviders, fn (PermissionProvider $a, PermissionProvider $b): int =>
            strnatcmp(get_class($a), get_class($b))
        );

        foreach ($sortedProviders as $provider) {
            $providerOrigins = $provider->provide($user->{$user->getKeyName()} ?? $user->id);
            $scoped = [];
            foreach ($providerOrigins as $origin) {
                if ($origin->scope->equals($scopeKey)) {
                    $scoped[] = $origin;
                }
            }
            $origins = array_merge($origins, $scoped);
        }

        return $origins;
    }
}