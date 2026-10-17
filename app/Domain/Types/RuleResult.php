<?php

namespace App\Domain\Types;

/**
 * Immutable decision result from a single policy evaluation.
 */
final class RuleResult
{
    public function __construct(
        public readonly string $outcome,
        public readonly string $policyCode,
        public readonly string $reason,
        public readonly bool $canOverride = false,
        public readonly array $metadata = [],
    ) {}

    public function isAllow(): bool
    {
        return $this->outcome === DecisionOutcome::ALLOW;
    }

    public function isDeny(): bool
    {
        return $this->outcome === DecisionOutcome::DENY;
    }

    public function canBeBypassed(): bool
    {
        return $this->canOverride;
    }

    public static function allow(string $policyCode, string $reason = '', array $metadata = []): self
    {
        return new self(DecisionOutcome::ALLOW, $policyCode, $reason, metadata: $metadata);
    }

    public static function deny(string $policyCode, string $reason, array $metadata = []): self
    {
        return new self(DecisionOutcome::DENY, $policyCode, $reason, metadata: $metadata);
    }

    public static function requireOverride(string $policyCode, string $reason): self
    {
        return new self(DecisionOutcome::REQUIRE_OVERRIDE, $policyCode, $reason, canOverride: true);
    }
}
