<?php

namespace App\Services\Sarpras\Automation;

use App\Models\User;
use App\Models\WorkOrder;

class TechnicianAssignmentService
{
    /**
     * Recommend technician(s) for a work order.
     * Returns array of ['user_id', 'name', 'score', 'reasons'] sorted by score desc.
     */
    public function recommend(WorkOrder $order, int $limit = 3): array
    {
        $asset = $order->asset;
        if (! $asset) {
            return [];
        }

        $categorySlug = $asset->category?->slug ?? $asset->asset_category ?? null;

        $technicianIds = usersHavingPermission('sarpras.technician.assignable');
        $candidates = User::query()
            ->whereIn('id', $technicianIds)
            ->with([
                'technicianSkills' => fn ($q) => $q->where('category_slug', $categorySlug),
                'technicianAvailability',
            ])
            ->get();

        $scored = [];
        foreach ($candidates as $user) {
            $score = $this->scoreCandidate($user, $order, $categorySlug);
            $scored[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'score' => $score['score'],
                'reasons' => $score['reasons'],
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }

    /**
     * Compute assignment confidence for a candidate.
     * Returns ['score' => int 0..100, 'reasons' => array].
     */
    public function scoreCandidate(User $user, WorkOrder $order, ?string $categorySlug): array
    {
        $score = 0;
        $reasons = [];

        $skill = $user->technicianSkills->first();
        if ($skill) {
            $score += $skill->proficiencyScore() * 0.5;
            $reasons[] = "Skill {$skill->proficiency}".($skill->is_certified ? ' (certified)' : '');
        } else {
            $score += 5;
            $reasons[] = 'No skill record — fallback';
        }

        $availability = $user->technicianAvailability;
        if ($availability?->isAvailable()) {
            $workload = $availability->workloadRatio();
            $bonus = (int) ((1.0 - $workload) * 30);
            $score += $bonus;
            $reasons[] = "Available, workload {$workload}";
        } else {
            $reasons[] = $availability?->status ?? 'No availability record';
        }

        $priorityBoost = match ($order->priority ?? 'medium') {
            'critical' => 10,
            'high' => 6,
            'medium' => 3,
            default => 0,
        };
        $score += $priorityBoost;
        if ($priorityBoost > 0) {
            $reasons[] = "Priority bonus +{$priorityBoost}";
        }

        $score = min(100, max(0, $score));

        return ['score' => $score, 'reasons' => $reasons];
    }

    /**
     * Assign best match to a work order.
     */
    public function autoAssign(WorkOrder $order): ?int
    {
        $ranked = $this->recommend($order, 1);
        if (empty($ranked)) {
            return null;
        }
        $top = $ranked[0];
        $order->assigned_to = $top['user_id'];
        $order->save();

        return $top['user_id'];
    }
}
