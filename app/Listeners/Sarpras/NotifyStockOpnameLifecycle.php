<?php

namespace App\Listeners\Sarpras;

use App\Events\Sarpras\StockOpnameCompleted;
use App\Events\Sarpras\StockOpnameStarted;
use App\Services\Sarpras\Automation\SarprasNotificationService;

class NotifyStockOpnameLifecycle
{
    public function __construct(protected SarprasNotificationService $notifier) {}

    public function handle($event): void
    {
        if ($event instanceof StockOpnameStarted) {
            $officers = $event->session->officers ?? [];
            $recipients = array_map(fn ($id) => (int) $id, is_array($officers) ? $officers : []);
            $recipients[] = (int) $event->organizer->id;
            $recipients = array_values(array_unique($recipients));

            $this->notifier->dispatch(
                eventType: 'StockOpnameStarted',
                recipientUserIds: $recipients,
                title: 'Stock Opname Started',
                message: "Sesi stock opname {$event->session->session_code} dimulai.",
                context: [
                    'reference_type' => 'stock_opname',
                    'reference_id' => $event->session->id,
                    'reference_code' => $event->session->session_code,
                ],
                priority: 'medium',
            );

            return;
        }

        if ($event instanceof StockOpnameCompleted) {
            $this->notifier->dispatch(
                eventType: 'StockOpnameCompleted',
                recipientUserIds: $this->sarprasAdmins(),
                title: 'Stock Opname Completed',
                message: "Sesi {$event->session->session_code} selesai dengan {$event->varianceCount} selisih.",
                context: [
                    'reference_type' => 'stock_opname',
                    'reference_id' => $event->session->id,
                    'reference_code' => $event->session->session_code,
                    'variance_count' => $event->varianceCount,
                ],
                priority: 'low',
            );
        }
    }

    protected function sarprasAdmins(): array
    {
        try {
            return usersHavingPermission('sarpras.auditor.auditable');
        } catch (\Throwable $e) {
            return [];
        }
    }
}
