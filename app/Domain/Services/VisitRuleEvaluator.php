<?php

namespace App\Domain\Services;

use App\Domain\Contracts\BoardingContextInterface;
use App\Domain\Contracts\BoardingRuleEvaluator;
use App\Domain\Types\RuleResult;

/**
 * Evaluates visit policy: quota, unrestricted, banned.
 */
final class VisitRuleEvaluator implements BoardingRuleEvaluator
{
    public function code(): string
    {
        return 'visit_policy';
    }

    public function supports(BoardingContextInterface $context): bool
    {
        return $context->eventType() === 'visit_request';
    }

    public function evaluate(BoardingContextInterface $context): RuleResult
    {
        $policy = $context->policy();
        $engine = BoardingRulesEngine::getInstance();
        $payload = $context->payload();
        $visitorCount = $payload['visitor_count'] ?? 1;

        if (! $policy) {
            return RuleResult::allow(
                'visit_policy',
                'No visit policy — defaulting to unrestricted.',
                ['strategy' => 'unrestricted']
            );
        }

        // Special permission bypass
        if ($context->isSpecialPermission() && $policy->allowsSpecialPermission()) {
            return RuleResult::allow(
                'visit_policy',
                'Visit approved via special permission.',
                ['strategy' => 'special_permission']
            );
        }

        // Banned → deny
        if ($policy->visit_strategy === 'banned') {
            return RuleResult::deny(
                'visit_policy',
                'Visits are currently banned.',
                ['strategy' => 'banned']
            );
        }

        // Unrestricted → allow
        if ($policy->visit_strategy === 'unrestricted') {
            // Still check visitor capacity
            $maxVisitors = $policy->max_visitors_per_visit;
            if ($maxVisitors && $visitorCount > $maxVisitors) {
                return RuleResult::deny(
                    'visit_policy',
                    "Visitor count ({$visitorCount}) exceeds max allowed ({$maxVisitors}).",
                    ['strategy' => 'capacity_limit', 'max_visitors' => $maxVisitors]
                );
            }

            return RuleResult::allow(
                'visit_policy',
                'Unrestricted visit policy.',
                ['strategy' => 'unrestricted']
            );
        }

        // Quota-based
        if ($policy->visit_strategy === 'quota') {
            $currentUsage = $engine->countUsageForCurrentPeriod(
                $context->student()->id, 'visit', $context->dormitory()->id,
                $policy->visit_quota_period
            );

            $quota = $policy->visit_quota ?? 0;
            $remaining = $quota - $currentUsage;

            if ($remaining <= 0) {
                return RuleResult::requireOverride(
                    'visit_policy',
                    "Visit quota exhausted ({$currentUsage}/{$quota}). Special permission required."
                );
            }

            return RuleResult::allow(
                'visit_policy',
                "Visit within quota. Remaining: {$remaining}.",
                ['strategy' => 'quota', 'remaining' => $remaining]
            );
        }

        return RuleResult::deny(
            'visit_policy',
            'Unknown visit strategy.',
            ['strategy' => 'unknown']
        );
    }
}
