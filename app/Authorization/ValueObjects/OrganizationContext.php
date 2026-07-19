<?php

declare(strict_types=1);

namespace App\Authorization\ValueObjects;

final readonly class OrganizationContext
{
    public function __construct(
        public ?string $schoolId,
        public string  $academicYearId,
        public string  $roleDimension,
        public string  $tenant = 'local',
    ) {}

    /**
     * Returns true only when schoolId is a concrete, non-empty value.
     *
     * Use this guard before passing schoolId to any tenant-aware service.
     * A null schoolId means the request has no school context bound yet —
     * middleware (BindOrganizationContext / SchoolContextMiddleware) did not
     * resolve a valid tenant.
     */
    public function hasValidSchool(): bool
    {
        return $this->schoolId !== null && $this->schoolId !== '';
    }

    public function toScopeKey(): ScopeKey
    {
        return ScopeKey::fromComponents(
            schoolId: $this->schoolId,
            academicYearId: $this->academicYearId,
            roleDimension: $this->roleDimension,
            tenantId: $this->tenant,
        );
    }
}