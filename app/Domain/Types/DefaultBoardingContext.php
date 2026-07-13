<?php

namespace App\Domain\Types;

use App\Domain\Contracts\BoardingContextInterface;
use App\Models\BoardingPolicy;
use App\Models\Dormitory;
use App\Models\Student;
use Carbon\CarbonImmutable;

/**
 * Default implementation of BoardingContextInterface.
 */
final class DefaultBoardingContext implements BoardingContextInterface
{
    public function __construct(
        private readonly Student $student,
        private readonly Dormitory $dormitory,
        private readonly ?BoardingPolicy $policy,
        private readonly string $eventType,
        private readonly CarbonImmutable $eventTime,
        private readonly array $payload = [],
        private readonly array $metadata = [],
        private readonly bool $isSpecialPermission = false,
    ) {}

    public function student(): Student
    {
        return $this->student;
    }

    public function dormitory(): Dormitory
    {
        return $this->dormitory;
    }

    public function policy(): ?BoardingPolicy
    {
        return $this->policy;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function eventTime(): CarbonImmutable
    {
        return $this->eventTime;
    }

    public function requestedAt(): CarbonImmutable
    {
        return $this->eventTime;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function isSpecialPermission(): bool
    {
        return $this->isSpecialPermission;
    }

    /**
     * Fluent factory for leaving requests.
     */
    public static function leaveRequest(Student $student, Dormitory $dormitory, ?BoardingPolicy $policy): self
    {
        return new self(
            $student,
            $dormitory,
            $policy,
            'leave_request',
            CarbonImmutable::now(),
            ['permit_type' => 'pulang']
        );
    }

    /**
     * Fluent factory for visit requests.
     */
    public static function visitRequest(Student $student, Dormitory $dormitory, ?BoardingPolicy $policy): self
    {
        return new self(
            $student,
            $dormitory,
            $policy,
            'visit_request',
            CarbonImmutable::now(),
            ['visitor_count' => 1]
        );
    }

    /**
     * Fluent factory for hospitalization.
     */
    public static function hospitalized(Student $student, Dormitory $dormitory, ?BoardingPolicy $policy): self
    {
        return new self(
            $student,
            $dormitory,
            $policy,
            'hospitalized',
            CarbonImmutable::now()
        );
    }
}
