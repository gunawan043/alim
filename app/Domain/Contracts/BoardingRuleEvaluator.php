<?php

namespace App\Domain\Contracts;

use App\Domain\Types\RuleResult;

/**
 * A single boarding policy evaluator.
 *
 * Implementations decide whether a domain request passes one specific policy.
 * Multiple evaluators are chained by BoardingRulesEngine.
 */
interface BoardingRuleEvaluator
{
    public function code(): string;

    public function supports(BoardingContextInterface $context): bool;

    public function evaluate(BoardingContextInterface $context): RuleResult;
}
