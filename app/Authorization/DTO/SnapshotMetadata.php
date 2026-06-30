<?php

declare(strict_types=1);

namespace App\Authorization\DTO;

use App\Authorization\Enums\SnapshotStatus;
use App\Authorization\ValueObjects\ScopeKey;
use DateTimeImmutable;

final readonly class SnapshotMetadata
{
    public function __construct(
        public DateTimeImmutable $createdAt,
        public ScopeKey $scopeKey,
        public int $version,
        public SnapshotStatus $status,
    ) {}

    public function equals(self $other): bool
    {
        return $this->scopeKey->value === $other->scopeKey->value
            && $this->version === $other->version
            && $this->status === $other->status
            && $this->createdAt == $other->createdAt;
    }
}
