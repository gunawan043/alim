<?php

declare(strict_types=1);

namespace App\Authorization\ValueObjects;

use App\Authorization\Exceptions\InvalidScopeException;
use App\Authorization\Support\CanonicalHasher;
use Illuminate\Support\Str;

final readonly class ScopeKey
{
    public const MAX_LENGTH = 255;

    private function __construct(public string $value) {}

    public static function forUser(mixed $userId, ?string $schoolId = null, ?string $academicYearId = null): self
    {
        $appContext = app()->make(\App\Authorization\ValueObjects\OrganizationContext::class);

        // Fall through to null when no school context is available.
        // null is deliberately kept as-is here; fromComponents() will
        // substitute '_no_school_' as the hash input so that a no-tenant
        // scope key never collides with a real school scope key.
        $schoolId = $schoolId ?? $appContext->schoolId;
        $academicYearId = $academicYearId ?? ($appContext->academicYearId ?? 'global');
        $roleDimension = $appContext->roleDimension ?? 'default';
        $tenant = $appContext->tenant ?? 'public';

        return self::fromComponents(
            schoolId: $schoolId,
            academicYearId: $academicYearId,
            roleDimension: $roleDimension,
            tenantId: $tenant,
        );
    }

    public static function fromComponents(
        ?string $schoolId,
        string $academicYearId,
        string $roleDimension,
        ?string $tenantId = null,
    ): self {
        // When schoolId is null we use a reserved token that can never be a
        // valid UUID.  The hash still uniquely identifies the no-school scope
        // but can never accidentally match any real-tenant cache entry.
        $canonical = implode('|', [
            $schoolId ?? '_no_school_',
            $academicYearId,
            $roleDimension,
            $tenantId ?? 'public',
        ]);

        $fingerprint = CanonicalHasher::sha256($canonical);

        return self::fromHash($fingerprint);
    }

    public static function fromHash(string $hash): self
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $hash)) {
            throw new InvalidScopeException(
                'Scope key must be a 64-character lowercase hex string.'
            );
        }

        if (Str::length($hash) > self::MAX_LENGTH) {
            throw new InvalidScopeException(
                'Scope key exceeds maximum length of '.self::MAX_LENGTH.'.'
            );
        }

        return new self($hash);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
