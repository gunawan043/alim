<?php

declare(strict_types=1);

namespace App\Authorization\DTO;

use App\Authorization\Enums\SnapshotStatus;
use App\Authorization\ValueObjects\ScopeKey;
use DateTimeImmutable;

final readonly class PermissionBag
{
    /**
     * @param  array<int, string>  $permissions
     * @param  array<int, string>  $revoked
     * @param  array<int, PermissionOrigin>  $origins
     */
    public function __construct(
        public array $permissions,
        public array $revoked,
        public string $fingerprint,
        public ?DateTimeImmutable $expiresAt,
        public SnapshotMetadata $metadata,
        public array $origins = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @return array<int, string>
     */
    public function getRevoked(): array
    {
        return $this->revoked;
    }

    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getMetadata(): SnapshotMetadata
    {
        return $this->metadata;
    }

    /**
     * @return array<int, PermissionOrigin>
     */
    public function getOrigins(): array
    {
        return $this->origins;
    }

    /**
     * Serialize this bag for cache storage.
     */
    public function toArray(): array
    {
        return [
            'permissions' => $this->permissions,
            'revoked' => $this->revoked,
            'fingerprint' => $this->fingerprint,
            'expires_at' => $this->expiresAt?->format(DateTimeImmutable::ATOM),
            'metadata' => [
                'created_at' => $this->metadata->createdAt->format(DateTimeImmutable::ATOM),
                'scope_key' => $this->metadata->scopeKey->value,
                'version' => $this->metadata->version,
                'status' => $this->metadata->status->value,
            ],
            'origins_count' => count($this->origins),
        ];
    }

    /**
     * Restore a PermissionBag from its cached array representation.
     */
    public static function fromArray(array $data): self
    {
        $createdAt = DateTimeImmutable::createFromFormat(
            DateTimeImmutable::ATOM,
            $data['metadata']['created_at']
        ) ?: new DateTimeImmutable;

        $expiresAt = isset($data['expires_at'])
            ? DateTimeImmutable::createFromFormat(
                DateTimeImmutable::ATOM,
                $data['expires_at']
            ) ?: null
            : null;

        $metadata = new SnapshotMetadata(
            createdAt: $createdAt,
            scopeKey: ScopeKey::fromHash($data['metadata']['scope_key']),
            version: (int) $data['metadata']['version'],
            status: SnapshotStatus::tryFrom($data['metadata']['status']) ?? SnapshotStatus::ACTIVE,
        );

        return new self(
            permissions: $data['permissions'] ?? [],
            revoked: $data['revoked'] ?? [],
            fingerprint: $data['fingerprint'],
            expiresAt: $expiresAt,
            metadata: $metadata,
            origins: [],
        );
    }
}
