<?php

namespace App\Domain\Types;

/**
 * Aggregate of policy decision and audit data for a domain request.
 *
 * @property-read array<int, RuleResult> $ruleResults
 */
final class RuleDecision
{
    /** @var array<int, RuleResult> */
    private array $ruleResults;

    /**
     * @param  array<int, RuleResult>  $ruleResults
     */
    public function __construct(array $ruleResults)
    {
        $this->ruleResults = $ruleResults;
    }

    /**
     * @return array<int, RuleResult>
     */
    public function getRuleResults(): array
    {
        return $this->ruleResults;
    }

    public function isAllowed(): bool
    {
        foreach ($this->ruleResults as $r) {
            if ($r->isDeny()) {
                return false;
            }
        }

        return true;
    }

    public function isDenied(): bool
    {
        return ! $this->isAllowed();
    }

    public function canBeBypassed(): bool
    {
        foreach ($this->ruleResults as $r) {
            if ($r->isDeny() && ! $r->canBeBypassed()) {
                return false;
            }
        }

        return true;
    }

    public function firstDenyReason(): ?string
    {
        foreach ($this->ruleResults as $r) {
            if ($r->isDeny()) {
                return $r->reason;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'allowed' => $this->isAllowed(),
            'can_bypass' => $this->canBeBypassed(),
            'first_deny_reason' => $this->firstDenyReason(),
            'rule_results' => array_map(fn (RuleResult $r) => [
                'outcome' => $r->outcome,
                'policy_code' => $r->policyCode,
                'reason' => $r->reason,
                'can_override' => $r->canOverride,
                'metadata' => $r->metadata,
            ], $this->ruleResults),
        ];
    }
}
