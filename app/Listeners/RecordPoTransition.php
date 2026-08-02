<?php

namespace App\Listeners;

use App\Events\GoodsReceived;
use App\Events\PoAccepted;
use App\Events\PoDelivered;
use App\Events\PoQcCompleted;
use App\Events\PoShipped;
use App\Events\PurchaseOrderCreated;
use App\Services\Vendor\AuditTrailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class RecordPoTransition implements ShouldQueue
{
    public string $queue = 'vendor-events';

    public function __construct(protected AuditTrailService $auditTrail) {}

    public function handleCreated(PurchaseOrderCreated $event): void
    {
        $this->auditTrail->recordAction(
            entityType: 'purchase_order',
            entityId: $event->purchaseOrder->id,
            action: 'created',
            payload: ['vendor_id' => $event->purchaseOrder->vendor_id, 'status' => $event->purchaseOrder->status],
        );

        Cache::tags(['purchase_orders', "vendor:{$event->purchaseOrder->vendor_id}"])->flush();
    }

    public function onAccepted(PoAccepted $event): void
    {
        $this->auditTrail->recordAction(
            entityType: 'purchase_order',
            entityId: $event->purchaseOrder->id,
            action: 'accepted_by_vendor',
        );
        Cache::tags(['purchase_orders', "vendor:{$event->purchaseOrder->vendor_id}"])->flush();
    }

    public function onShipped(PoShipped $event): void
    {
        $this->auditTrail->recordAction(
            entityType: 'purchase_order',
            entityId: $event->purchaseOrder->id,
            action: 'shipped',
            payload: ['tracking_number' => $event->purchaseOrder->tracking_number ?? null],
        );
    }

    public function onDelivered(PoDelivered $event): void
    {
        $this->auditTrail->recordAction(
            entityType: 'purchase_order',
            entityId: $event->purchaseOrder->id,
            action: 'delivered',
        );
    }

    public function onQcCompleted(PoQcCompleted $event): void
    {
        $this->auditTrail->recordAction(
            entityType: 'purchase_order',
            entityId: $event->purchaseOrder->id,
            action: 'qc_completed',
            payload: ['qc_status' => $event->purchaseOrder->qc_status ?? null],
        );
    }

    public function onGoodsReceived(GoodsReceived $event): void
    {
        $this->auditTrail->recordAction(
            entityType: 'goods_receipt',
            entityId: $event->goodsReceipt->id,
            action: 'received',
            payload: ['po_id' => $event->goodsReceipt->purchase_order_id],
        );
        Cache::tags(['goods_receipts', "vendor:{$event->goodsReceipt->vendor_id}"])->flush();
    }
}
