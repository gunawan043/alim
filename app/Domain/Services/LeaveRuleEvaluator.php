<?php

namespace App\Domain\Services;

use App\Domain\Contracts\BoardingContextInterface;
use App\Domain\Contracts\BoardingRuleEvaluator;
use App\Domain\Types\RuleResult;

/**
 * Evaluates leave/request policy: quota, unrestricted, banned.
 */
final class LeaveRuleEvaluator implements BoardingRuleEvaluator
{
    public function code(): string
    {
        return 'leave_policy';
    }

    public function supports(BoardingContextInterface $context): bool
    {
        return in_array($context->eventType(), [
            'leave_request',
            'special_permission',
        ], true);
    }

    public function evaluate(BoardingContextInterface $context): RuleResult
    {
        $policy = $context->policy();
        $studentId = $context->student()->id;
        $dormitoryId = $context->dormitory()->id;
        $engine = BoardingRulesEngine::getInstance();

        // Case 1: no policy assigned → fall back to unrestricted
        if (! $policy) {
            return RuleResult::allow(
                'leave_policy',
                'No policy assigned — defaulting to unrestricted.',
                ['strategy' => 'unrestricted']
            );
        }

        // Case 2: special permission bypass
        if ($context->isSpecialPermission() && $policy->allowsSpecialPermission()) {
            $payload = $context->payload();
            $type = $payload['special_permission_type'] ?? $payload['reason'] ?? 'custom';

            return RuleResult::allow(
                'leave_policy',
                "Special permission granted for: {$type}",
                ['strategy' => 'special_permission', 'type' => $type]
            );
        }

        // Case 3: banned → deny
        if ($policy->isBanned()) {
            return RuleResult::deny(
                'leave_policy',
                'Leave is currently banned for this dormitory.',
                ['strategy' => 'banned']
            );
        }

        // Case 4: unrestricted → allow
        if ($policy->isUnrestricted()) {
            return RuleResult::allow(
                'leave_policy',
                'Unrestricted leave policy.',
                ['strategy' => 'unrestricted']
            );
        }

        // Case 5: quota-based — check remaining quota
        if ($policy->isQuotaBased()) {
            $currentUsage = $engine->countUsageForCurrentPeriod(
                $studentId, 'leave', $dormitoryId, $policy->leave_quota_period
            );

            $quota = $policy->leave_quota ?? 0;
            $remaining = $quota - $currentUsage;

            if ($remaining <= 0) {
                return RuleResult::requireOverride(
                    'leave_policy',
                    "Leave quota exhausted ({$currentUsage}/{$quota}). Special permission required."
                );
            }

            return RuleResult::allow(
                'leave_policy',
                "Within quota. Remaining: {$remaining} leave(s).",
                [
                    'strategy' => 'quota',
                    'quota' => $quota,
                    'used' => $currentUsage,
                    'remaining' => $remaining,
                ]
            );
        }

        return RuleResult::deny(
            'leave_policy',
            'Unknown leave strategy.',
            ['strategy' => 'unknown']
        );
    }
}
