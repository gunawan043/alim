<?php

namespace App\Listeners\Sarpras;

use App\Events\Sarpras\SlATrackerEscalated;
use App\Events\Sarpras\SlATrackerOverdue;
use App\Events\Sarpras\SlATrackerWarned;
use App\Services\Sarpras\Automation\SarprasNotificationService;

class NotifySlAEscalation
{
    public function __construct(protected SarprasNotificationService $notifier) {}

    public function handle($event): void
    {
        $type = match (true) {
            $event instanceof SlATrackerOverdue => 'SlATrackerOverdue',
            $event instanceof SlATrackerWarned => 'SlATrackerWarned',
            $event instanceof SlATrackerEscalated => 'SlATrackerEscalated',
            default => null,
        };
        if (! $type) {
            return;
        }

        $title = match ($type) {
            'SlATrackerOverdue' => 'SLA Overdue',
            'SlATrackerWarned' => 'SLA Warning',
            'SlATrackerEscalated' => 'SLA Escalated',
        };

        $message = match ($type) {
            'SlATrackerOverdue' => "Tracker overdue ({$event->overdueMinutes}m).",
            'SlATrackerWarned' => "Tracker approaching deadline ({$event->remainingMinutes}m left).",
            'SlATrackerEscalated' => "Tracker escalated to level {$event->escalationLevel}.",
        };

        $priority = match ($type) {
            'SlATrackerOverdue' => 'high',
            'SlATrackerEscalated' => 'high',
            default => 'medium',
        };

        $this->notifier->dispatch(
            eventType: $type,
            recipientUserIds: $this->sarprasAdmins(),
            title: $title,
            message: $message,
            context: [
                'reference_type' => 'sla_tracker',
                'reference_id' => $event->tracker->id,
                'workflow_type' => $event->tracker->workflow_type,
                'entity_table' => $event->tracker->entity_table,
                'entity_id' => $event->tracker->entity_id,
            ],
            priority: $priority,
        );
    }

    protected function sarprasAdmins(): array
    {
        try {
            return usersHavingPermission('sarpras.administrator.accessible');
        } catch (\Throwable $e) {
            return [];
        }
    }
}
