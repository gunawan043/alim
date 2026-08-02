<?php

namespace App\Listeners;

use App\Events\QuotationAccepted;
use App\Events\QuotationAwarded;
use App\Events\QuotationSubmitted;
use App\Services\Vendor\AuditTrailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class RecordQuotationTransition implements ShouldQueue
{
    public string $queue = 'vendor-events';

    public function __construct(protected AuditTrailService $auditTrail) {}

    public function handleSubmitted(QuotationSubmitted $event): void
    {
        $this->auditTrail->recordAction(
            entityType: 'quotation',
            entityId: $event->quotation->id,
            action: 'submitted',
            payload: ['vendor_id' => $event->quotation->vendor_id, 'rfq_id' => $event->quotation->rfq_id],
        );

        Cache::tags(['quotations', "vendor:{$event->quotation->vendor_id}"])->flush();
    }

    public function handleAwarded(QuotationAwarded $event): void
    {
        $this->auditTrail->recordAction(
            entityType: 'quotation',
            entityId: $event->quotation->id,
            action: 'awarded',
            payload: ['po_id' => $event->purchaseOrder->id, 'vendor_id' => $event->quotation->vendor_id],
        );

        Cache::tags(['quotations', "vendor:{$event->quotation->vendor_id}", "rfq:{$event->quotation->rfq_id}"])->flush();
    }

    public function handleAccepted(QuotationAccepted $event): void
    {
        $this->auditTrail->recordAction(
            entityType: 'quotation',
            entityId: $event->quotation->id,
            action: 'accepted_by_vendor',
            payload: ['vendor_id' => $event->quotation->vendor_id],
        );
    }
}
