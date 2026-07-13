<?php

namespace App\Domain\Contracts;

use App\Models\BoardingPolicy;
use App\Models\Dormitory;
use App\Models\Student;
use Carbon\CarbonImmutable;

/**
 * Context passed to every boarding policy evaluator.
 *
 * Holds data the engine uses to make a decision. Populated at request entry by
 * callers (web controllers, listeners, queued jobs).
 */
interface BoardingContextInterface
{
    public function student(): Student;

    public function dormitory(): Dormitory;

    public function policy(): ?BoardingPolicy;

    public function eventType(): string;

    public function eventTime(): CarbonImmutable;

    public function requestedAt(): CarbonImmutable;

    /**
     * Domain payload (permit_type, return_datetime, etc.).
     *
     * @return array<string, mixed>
     */
    public function payload(): array;

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array;

    public function isSpecialPermission(): bool;
}
