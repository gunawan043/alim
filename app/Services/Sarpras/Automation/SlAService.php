<?php

namespace App\Services\Sarpras\Automation;

use App\Models\SlADefinition;
use App\Models\SlaTracker;
use App\Models\WorkOrder;
use Carbon\Carbon;

class SlAService
{
    /**
     * Start a SLA tracker for an entity.
     */
    public function startTracker(
        string $workflowType,
        string $entityTable,
        int $entityId,
        string $priority = 'medium',
    ): SlaTracker {
        $definition = SlADefinition::forWorkflow($workflowType, $priority);

        $resolutionMinutes = $definition?->resolution_minutes ?? 1440;

        $tracker = SlaTracker::create([
            'workflow_type' => $workflowType,
            'entity_table' => $entityTable,
            'entity_id' => $entityId,
            'priority' => $priority,
            'started_at' => now(),
            'deadline_at' => now()->addMinutes($resolutionMinutes),
            'status' => 'on_track',
            'escalation_level' => 0,
        ]);

        if ($definition?->response_minutes !== null) {
            $tracker->response_deadline_at = $tracker->started_at->copy()
                ->addMinutes($definition->response_minutes);
        }

        return $tracker->fresh();
    }

    /**
     * Recompute status for a tracker (called by scheduler).
     */
    public function recompute(SlaTracker $tracker): SlaTracker
    {
        if ($tracker->completed_at) {
            return $tracker;
        }

        $remaining = $tracker->remaining_minutes;
        $overdue = $tracker->overdue_minutes;

        if ($overdue > 0) {
            $tracker->status = 'overdue';
            $tracker->save();

            event(new \App\Events\Sarpras\SlATrackerOverdue($tracker, $overdue));
        } elseif ($remaining > 0 && $remaining <= 60) {
            $tracker->status = 'warning';
            $tracker->save();

            event(new \App\Events\Sarpras\SlATrackerWarned($tracker, $remaining));
        } else {
            $tracker->status = 'on_track';
            $tracker->save();
        }

        return $tracker;
    }

    /**
     * Pause / resume / close lifecycle.
     */
    public function pause(SlaTracker $tracker, ?string $reason = null): SlaTracker
    {
        if (! $tracker->paused_at) {
            $tracker->paused_at = now();
            $tracker->save();
        }
        return $tracker;
    }

    public function resume(SlaTracker $tracker): SlaTracker
    {
        if ($tracker->paused_at) {
            $elapsed = (int) $tracker->paused_at->diffInSeconds(now());
            $tracker->deadline_at = $tracker->deadline_at->addSeconds($elapsed);
            $tracker->accumulated_seconds = (int) $tracker->paused_at->diffInSeconds($tracker->started_at);
            $tracker->paused_at = null;
            $tracker->save();
        }
        return $tracker;
    }

    public function complete(SlaTracker $tracker): SlaTracker
    {
        $tracker->completed_at = now();
        $tracker->status = 'completed';
        $tracker->save();
        return $tracker;
    }

    /**
     * Auto-escalate — bump escalation level, re-dispatch event.
     */
    public function escalate(SlaTracker $tracker): SlaTracker
    {
        $newLevel = $tracker->escalation_level + 1;
        $tracker->escalate($newLevel);

        event(new \App\Events\Sarpras\SlATrackerEscalated($tracker, $newLevel));
        return $tracker;
    }

    /**
     * Process a WorkOrder through SLA pipeline.
     * Returns the tracker (fresh).
     */
    public function trackWorkOrder(WorkOrder $order): ?SlaTracker
    {
        $existing = SlaTracker::where('entity_table', 'work_orders')
            ->where('entity_id', $order->id)
            ->first();

        $priority = $order->priority ?? 'medium';

        return $existing ?? $this->startTracker(
            'work_order',
            'work_orders',
            $order->id,
            $priority,
        );
    }

    /**
     * Bulk evaluate all open trackers. Called by scheduler.
     */
    public function evaluateAll(): int
    {
        $open = SlaTracker::whereNull('completed_at')->get();
        $changes = 0;
        foreach ($open as $tracker) {
            $before = $tracker->status;
            $this->recompute($tracker);
            if ($tracker->status !== $before) {
                $changes++;
            }
        }
        return $changes;
    }

    /**
     * Bulk auto-escalate anything overdue > 1h.
     */
    public function runAutoEscalation(): int
    {
        $escalated = 0;
        $overdue = SlaTracker::where('status', 'overdue')
            ->whereNull('completed_at')
            ->where('deadline_at', '<', now()->subHour())
            ->get();
        foreach ($overdue as $tracker) {
            $this->escalate($tracker);
            $escalated++;
        }
        return $escalated;
    }
}