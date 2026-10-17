<?php

namespace App\Domain\Services;

use App\Domain\Contracts\BoardingContextInterface;
use App\Domain\Contracts\BoardingRuleEvaluator;
use App\Domain\Types\RuleResult;

/**
 * Evaluates hospitalization health policy — always allows (override).
 * Hospitalization and recovery are exempt from quota.
 */
final class HospitalizationRuleEvaluator implements BoardingRuleEvaluator
{
    public function code(): string
    {
        return 'health_policy';
    }

    public function supports(BoardingContextInterface $context): bool
    {
        return in_array($context->eventType(), [
            'hospitalized',
            'recovered',
        ], true);
    }

    public function evaluate(BoardingContextInterface $context): RuleResult
    {
        // Health emergencies always bypass quota.
        // The policy's special_permission_allowed controls whether the hospitalization
        // itself requires special-permission-flagging (e.g., extended stays).
        $allowUnconditionally = $context->policy() !== null && ! $context->policy()->is_active;

        if ($allowUnconditionally || $context->isSpecialPermission()) {
            return RuleResult::allow(
                'health_policy',
                'Hospitalization exempt from policy (emergency).',
                ['strategy' => 'exempt']
            );
        }

        return RuleResult::allow(
            'health_policy',
            'Health event allowed — quota exempt.',
            ['strategy' => 'exempt']
        );
    }
}
