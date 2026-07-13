<?php

declare(strict_types=1);

namespace App\Authorization\ValueObjects;

final readonly class OrganizationContext
{
    public function __construct(
        public string $schoolId,
        public string $academicYearId,
        public string $roleDimension,
        public string $tenant = 'local',
    ) {}

    public function toScopeKey(): ScopeKey
    {
        return ScopeKey::fromComponents(
            $this->schoolId,
            $this->academicYearId,
            $this->roleDimension,
            $this->tenant,
        );
    }
}