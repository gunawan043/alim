<?php

namespace App\Domain\Services;

use App\Domain\Contracts\BoardingContextInterface;
use App\Domain\Contracts\BoardingRuleEvaluator;
use App\Domain\Types\QuotaPeriod;
use App\Domain\Types\RuleDecision;
use App\Models\BoardingPolicy;
use App\Models\Dormitory;
use App\Models\DormitoryPolicyAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The central Boarding Rules Engine.
 *
 * Delegates to pluggable policy evaluators. Supports policy lookup,
 * caching, and chain composition. New policies register themselves
 * without modifying existing code.
 */
final class BoardingRulesEngine
{
    private Collection $evaluators;

    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->evaluators = collect([]);
    }

    /**
     * Register a policy evaluator with the engine.
     */
    public function registerEvaluator(BoardingRuleEvaluator $evaluator): void
    {
        $this->evaluators->push($evaluator);
    }

    /**
     * Count registered evaluators.
     */
    public function getEvaluatorCount(): int
    {
        return $this->evaluators->count();
    }

    /**
     * List evaluator codes registered.
     */
    public function listEvaluators(): array
    {
        return $this->evaluators->map(fn (BoardingRuleEvaluator $e) => $e->code())->toArray();
    }

    /**
     * Get evaluators for testing.
     *
     * @return array<int, BoardingRuleEvaluator>
     */
    public function getEvaluators(): array
    {
        return $this->evaluators->toArray();
    }

    /**
     * Evaluate a boarding request against all registered policies.
     *
     * Caching strategy:
     *  - Special permission decisions are NEVER cached — admin override
     *    decisions must always reflect the latest policy/usage state.
     *  - The cache key includes the policy ID and updated_at hash so a
     *    policy edit automatically invalidates cached decisions without
     *    needing observer plumbing.
     *  - The cache key includes the current quota period so a decision
     *    cached mid-month does not leak into the next month.
     *  - The cache TTL is a safety net; cache busting also happens on
     *    BoardingPolicy updates via BoardingPolicyObserver (registered in
     *    the model boot method).
     */
    public function evaluate(BoardingContextInterface $context): RuleDecision
    {
        if ($context->isSpecialPermission()) {
            return $this->runEvaluators($context);
        }

        $policy = $context->policy();
        $policyFingerprint = $policy
            ? sprintf('%s_%s', $policy->id, $policy->updated_at?->timestamp ?? 0)
            : 'default';
        $quotaPeriod = $policy?->quota_period ?? QuotaPeriod::MONTHLY;
        $dateSegment = match ($quotaPeriod) {
            QuotaPeriod::WEEKLY => 'Y-W',
            QuotaPeriod::MONTHLY => 'Y-m',
            QuotaPeriod::YEARLY => 'Y',
            default => 'Y-m',
        };

        $cacheKey = sprintf(
            'rules_engine_%s_%s_%s_%s_%s_%s',
            $context->student()->id,
            $context->dormitory()->id,
            $context->eventType(),
            $policyFingerprint,
            $quotaPeriod,
            md5(json_encode($context->payload()))
        );

        $cached = Cache::remember($cacheKey, 300, fn (): array => $this->runEvaluatorsRaw($context));

        return new RuleDecision($cached);
    }

    /**
     * Run all evaluators and return a RuleDecision. Used when caching is bypassed.
     *
     * @return array<string, mixed>
     */
    private function runEvaluators(BoardingContextInterface $context): RuleDecision
    {
        return new RuleDecision($this->runEvaluatorsRaw($context));
    }

    /**
     * @return array<string, mixed>
     */
    private function runEvaluatorsRaw(BoardingContextInterface $context): array
    {
        $results = [];
        foreach ($this->evaluators as $evaluator) {
            if (! $evaluator->supports($context)) {
                continue;
            }

            $results[$evaluator->code()] = $evaluator->evaluate($context);
        }

        return $results;
    }

    /**
     * Quick-check: does the current policy allow the given action?
     *
     * @return array<string, mixed>
     */
    public function canLeave(string $studentId, string $dormitoryId, array $payload = []): array
    {
        return $this->getDecision('leave_request', $studentId, $dormitoryId, $payload)->toArray();
    }

    /**
     * Quick-check: does the current policy allow a visit?
     *
     * @return array<string, mixed>
     */
    public function canVisit(string $studentId, string $dormitoryId, int $visitorCount = 1): array
    {
        return $this->getDecision('visit_request', $studentId, $dormitoryId, ['visitor_count' => $visitorCount])->toArray();
    }

    /**
     * Quick-check: is special permission available for a student?
     *
     * @return array<string, mixed>
     */
    public function canSpecialPermission(string $studentId, string $dormitoryId, string $reason): array
    {
        $decision = $this->getDecision('special_permission', $studentId, $dormitoryId, ['reason' => $reason], true);

        $result = $decision->toArray();
        $result['special_permission_type'] = $reason;

        return $result;
    }

    /**
     * Get the boarding policy applicable to a student+dormitory combo.
     */
    public function getApplicablePolicy(string $studentId, string $dormitoryId): ?BoardingPolicy
    {
        $cacheKey = sprintf('policy_%s_%s', $studentId, $dormitoryId);

        return Cache::remember($cacheKey, 600, function () use ($studentId, $dormitoryId): ?BoardingPolicy {
            $student = \App\Models\Student::find($studentId);
            if (! $student) {
                return null;
            }

            $resident = \App\Models\DormitoryResident::with('dormitory')
                ->where('student_id', $studentId)
                ->where('dormitory_id', $dormitoryId)
                ->where('is_active', true)
                ->first();

            if (! $resident) {
                return null;
            }

            $assignments = DormitoryPolicyAssignment::with('policy')
                ->where('target_id', $dormitoryId)
                ->where('policy_assignment_type', 'dormitory')
                ->where(fn ($q) => $q
                    ->whereNull('effective_from')->orWhere('effective_from', '<=', now())
                )
                ->where(fn ($q) => $q
                    ->whereNull('effective_until')->orWhere('effective_until', '>=', now())
                )
                ->orderByDesc('priority')
                ->get();

            foreach ($assignments as $assignment) {
                if ($assignment->policy->is_active) {
                    return $assignment->policy;
                }
            }

            // Fallback: first active policy
            return BoardingPolicy::where('is_active', true)->first();
        });
    }

    /**
     * Count leave/visit usage for the current quota period.
     *
     * Counts from approved+returned events in the timeline, NOT from
     * per-student counters.
     *
     * @param  'leave'|'visit'  $eventType
     */
    public function countUsageForCurrentPeriod(string $studentId, string $eventType, string $dormitoryId, string $period): int
    {
        $cacheKey = sprintf('usage_%s_%s_%s_%s_%s', $studentId, $eventType, $dormitoryId, $period, now()->format($period === QuotaPeriod::WEEKLY ? 'Y-W' : 'Y-m'));

        return Cache::remember($cacheKey, 60, function () use ($studentId, $eventType, $period): int {
            $student = \App\Models\Student::find($studentId);
            if (! $student) {
                return 0;
            }

            // Determine which models to count for timeline events
            $allowedTypes = match ($eventType) {
                'leave' => [
                    \App\Models\BoardingTimelineEvent::TYPE_LEAVE_STARTED,
                    \App\Models\BoardingTimelineEvent::TYPE_LEAVE_APPROVED,
                ],
                'visit' => [
                    \App\Models\BoardingTimelineEvent::TYPE_VISIT_APPROVED,
                    \App\Models\BoardingTimelineEvent::TYPE_VISIT_CHECK_IN,
                ],
                default => [],
            };

            if (empty($allowedTypes)) {
                return 0;
            }

            $rangeStart = \App\Domain\Types\QuotaPeriod::rangeBound($period);
            $rangeEnd = $rangeStart->copy()->addDay(); // first day of next range unit

            return \App\Models\BoardingTimelineEvent::where('student_id', $studentId)
                ->whereIn('event_type', $allowedTypes)
                ->where('event_at', '>=', $rangeStart)
                ->where('event_at', '<', $rangeEnd)
                ->count();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function getDecision(
        string $eventType,
        string $studentId,
        string $dormitoryId,
        array $payload = [],
        bool $isSpecial = false
    ): RuleDecision {
        $dormitory = Dormitory::with('policyAssignments.policy')->findOrFail($dormitoryId);
        $student = \App\Models\Student::find($studentId);
        $policy = $this->getApplicablePolicy($studentId, $dormitoryId);

        $context = new DefaultBoardingContext(
            $student,
            $dormitory,
            $policy,
            $eventType,
            \Carbon\CarbonImmutable::now(),
            $payload,
            [],
            $isSpecial
        );

        return $this->evaluate($context);
    }
}
