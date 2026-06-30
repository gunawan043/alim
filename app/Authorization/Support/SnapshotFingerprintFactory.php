<?php

declare(strict_types=1);

namespace App\Authorization\Support;

use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\DTO\SnapshotFingerprint;
use DateTimeImmutable;
use DateTimeZone;

final class SnapshotFingerprintFactory
{
    public const ALGORITHM = 'sha256-canonical-jcs';

    public static function fromOrigins(array $origins, ?DateTimeImmutable $now = null): SnapshotFingerprint
    {
        $sorted = PermissionTreeNormalizer::normalize($origins);

        $canonical = self::canonicalize($sorted);

        $hash = hash('sha256', $canonical);

        return new SnapshotFingerprint(
            hash: $hash,
            algorithm: self::ALGORITHM,
            createdAt: $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC')),
        );
    }

    /**
     * @param array<int, PermissionOrigin> $sortedOrigins
     */
    private static function canonicalize(array $sortedOrigins): string
    {
        $entries = [];

        foreach ($sortedOrigins as $origin) {
            $entries[] = implode(
                "\x1f",
                [
                    $origin->permission,
                    (string) $origin->scope,
                    $origin->source->value,
                    $origin->provider,
                    $origin->reason,
                ]
            );
        }

        return implode("\x1e", $entries);
    }
}