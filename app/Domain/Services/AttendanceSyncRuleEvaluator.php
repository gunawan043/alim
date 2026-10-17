<?php

namespace App\Domain\Services;

use App\Domain\Contracts\BoardingContextInterface;
use App\Domain\Contracts\BoardingRuleEvaluator;
use App\Domain\Types\RuleResult;

/**
 * Checks whether the rules engine should auto-sync academic attendance
 * when a boarding event occurs.
 */
final class AttendanceSyncRuleEvaluator implements BoardingRuleEvaluator
{
    public function code(): string
    {
        return 'attendance_sync';
    }

    public function supports(BoardingContextInterface $context): bool
    {
        return in_array($context->eventType(), [
            'leave_started',
            'returned',
            'visit_check_in',
            'visit_check_out',
        ], true);
    }

    public function evaluate(BoardingContextInterface $context): RuleResult
    {
        if ($context->policy() && $context->policy()->auto_sync_academic_attendance) {
            return RuleResult::allow(
                'attendance_sync',
                'Auto-sync enabled for attendance.',
                ['sync_enabled' => true]
            );
        }

        return RuleResult::deny(
            'attendance_sync',
            'Auto-sync disabled by policy.',
            ['sync_enabled' => false]
        );
    }
}
