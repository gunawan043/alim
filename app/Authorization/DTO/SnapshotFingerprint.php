<?php

declare(strict_types=1);

namespace App\Authorization\DTO;

use DateTimeImmutable;

final readonly class SnapshotFingerprint
{
    public function __construct(
        public string $hash,
        public string $algorithm,
        public DateTimeImmutable $createdAt,
    ) {}
}
