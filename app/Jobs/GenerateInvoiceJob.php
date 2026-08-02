<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $purchaseOrderId
    ) {}

    public function handle(): void
    {
        $po = \App\Models\PurchaseOrder::find($this->purchaseOrderId);
        if (! $po || ! $po->delivery || ! $po->delivery->hasDeliveryNote()) {
            return;
        }

        // Auto-trigger invoice generation after delivery
        $invoiceApprovalService = app(\App\Services\InvoiceApprovalService::class);
        $invoice = $invoiceApprovalService->create(
            auth()->id(),
            $po->vendor_id,
            [
                'purchase_order_id' => $po->id,
                'total_amount' => $po->total_amount,
                'currency' => $po->currency,
                'invoice_number' => "INV-PO-{$po->po_number}",
                'invoice_date' => now()->toDateString(),
            ]
        );

        Notification::create([
            'user_id' => $po->submitted_by,
            'type' => 'invoice_generated',
            'title' => 'Invoice Generated',
            'message' => "Invoice {$invoice->approval_number} has been created for PO {$po->po_number}",
            'data' => ['invoice_id' => $invoice->id],
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        // Log the failure
    }
}
