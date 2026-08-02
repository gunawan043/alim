<?php

namespace App\Listeners;

use App\Events\InvoiceApproved;
use App\Services\Vendor\AuditTrailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class RecordInvoiceApprovalTransition implements ShouldQueue
{
    public string $queue = 'vendor-events';

    public function __construct(protected AuditTrailService $auditTrail) {}

    public function handle(InvoiceApproved $event): void
    {
        $invoice = $event->invoice;

        $this->auditTrail->recordAction(
            entityType: 'invoice_approval',
            entityId: $invoice->id,
            action: 'approved',
            payload: [
                'vendor_id' => $invoice->vendor_id,
                'amount' => $invoice->amount ?? null,
                'approver_id' => $invoice->final_approver_id ?? null,
            ],
        );

        Cache::tags(['invoice_approvals', "vendor:{$invoice->vendor_id}"])->flush();
    }
}
