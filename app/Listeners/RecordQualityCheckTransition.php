<?php

namespace App\Listeners;

use App\Events\QualityChecked;
use App\Services\Vendor\AuditTrailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class RecordQualityCheckTransition implements ShouldQueue
{
    public string $queue = 'vendor-events';

    public function __construct(protected AuditTrailService $auditTrail) {}

    public function handle(QualityChecked $event): void
    {
        $qc = $event->qualityCheck;

        $this->auditTrail->recordAction(
            entityType: 'quality_check',
            entityId: $qc->id,
            action: 'checked',
            payload: ['status' => $qc->status ?? 'completed', 'po_id' => $qc->purchase_order_id],
        );

        Cache::tags(['quality_checks', 'purchase_orders'])->flush();
    }
}
