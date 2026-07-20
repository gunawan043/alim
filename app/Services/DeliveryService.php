<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\DeliveryTracking;
use App\Models\PurchaseOrder;
use App\Services\Sarpras\StateMachine;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    public const TRANSITIONS = [
        'delivery' => [
            DeliveryTracking::STATUS_PENDING => [
                DeliveryTracking::STATUS_PICKED_UP,
                DeliveryTracking::STATUS_FAILED,
            ],
            DeliveryTracking::STATUS_PICKED_UP => [
                DeliveryTracking::STATUS_IN_TRANSIT,
                DeliveryTracking::STATUS_FAILED,
            ],
            DeliveryTracking::STATUS_IN_TRANSIT => [
                DeliveryTracking::STATUS_OUT_FOR_DELIVERY,
                DeliveryTracking::STATUS_FAILED,
            ],
            DeliveryTracking::STATUS_OUT_FOR_DELIVERY => [
                DeliveryTracking::STATUS_DELIVERED,
                DeliveryTracking::STATUS_FAILED,
            ],
            DeliveryTracking::STATUS_FAILED => [
                DeliveryTracking::STATUS_RETURNED,
                DeliveryTracking::STATUS_OUT_FOR_DELIVERY,
            ],
        ],
    ];

    public function __construct(private StateMachine $machine) {}

    public function create(PurchaseOrder $po, array $data = []): DeliveryTracking
    {
        $delivery = $po->deliveries()->create([
            'tracking_number' => $data['tracking_number']
                ?? ('TRK-' . strtoupper(substr(md5(uniqid('', true)), 0, 12))),
            'courier' => $data['courier'] ?? null,
            'service_type' => $data['service_type'] ?? null,
            'dispatched_date' => $data['dispatched_date'] ?? now()->toDateString(),
            'estimated_arrival' => $data['estimated_arrival'] ?? null,
            'status' => DeliveryTracking::STATUS_PENDING,
            'current_location' => $data['current_location'] ?? null,
            'delivery_notes' => $data['delivery_notes'] ?? null,
        ]);

        $delivery->addTrackingEvent('Shipment registered', [
            'courier' => $delivery->courier,
            'tracking_number' => $delivery->tracking_number,
        ]);
        $delivery->save();

        $this->audit($delivery, 'created');

        return $delivery;
    }

    public function updateStatus(DeliveryTracking $delivery, string $status, ?string $notes = null, ?string $location = null): DeliveryTracking
    {
        $this->machine->assert('delivery', $delivery->status, $status);

        $delivery->update([
            'status' => $status,
            'current_location' => $location ?? $delivery->current_location,
            'delivery_notes' => $notes
                ? ($delivery->delivery_notes . "\n[{$status}] {$notes}")
                : $delivery->delivery_notes,
        ]);

        $delivery->addTrackingEvent("Status updated to {$status}", [
            'notes' => $notes,
            'location' => $location,
        ]);
        $delivery->save();

        $this->audit($delivery, 'status_changed', ['new_status' => $status]);

        return $delivery;
    }

    public function markPickedUp(DeliveryTracking $delivery): DeliveryTracking
    {
        return $this->updateStatus($delivery, DeliveryTracking::STATUS_PICKED_UP);
    }

    public function markInTransit(DeliveryTracking $delivery): DeliveryTracking
    {
        return $this->updateStatus($delivery, DeliveryTracking::STATUS_IN_TRANSIT);
    }

    public function markOutForDelivery(DeliveryTracking $delivery): DeliveryTracking
    {
        return $this->updateStatus($delivery, DeliveryTracking::STATUS_OUT_FOR_DELIVERY);
    }

    public function markDelivered(DeliveryTracking $delivery, string $recipientUserId, string $recipientName, ?string $notes = null): DeliveryTracking
    {
        $this->machine->assert('delivery', $delivery->status, DeliveryTracking::STATUS_DELIVERED);

        $delivery->update([
            'status' => DeliveryTracking::STATUS_DELIVERED,
            'recipient_user_id' => $recipientUserId,
            'recipient_name' => $recipientName,
            'received_at' => now(),
            'actual_arrival' => now(),
            'delivery_notes' => $notes
                ? ($delivery->delivery_notes . "\n[Delivered] {$notes}")
                : $delivery->delivery_notes,
        ]);

        $delivery->addTrackingEvent('Delivered to recipient', [
            'recipient_user_id' => $recipientUserId,
            'recipient_name' => $recipientName,
            'notes' => $notes,
        ]);
        $delivery->save();

        $this->audit($delivery, 'delivered');

        return $delivery;
    }

    public function markFailed(DeliveryTracking $delivery, string $reason): DeliveryTracking
    {
        $this->machine->assert('delivery', $delivery->status, DeliveryTracking::STATUS_FAILED);

        $delivery->update([
            'status' => DeliveryTracking::STATUS_FAILED,
            'delivery_notes' => $delivery->delivery_notes . "\n[Failed] {$reason}",
        ]);

        $delivery->addTrackingEvent('Delivery failed', ['reason' => $reason]);
        $delivery->save();

        $this->audit($delivery, 'failed', ['reason' => $reason]);

        return $delivery;
    }

    public function markReturned(DeliveryTracking $delivery, ?string $reason = null): DeliveryTracking
    {
        $this->machine->assert('delivery', $delivery->status, DeliveryTracking::STATUS_RETURNED);

        $delivery->update([
            'status' => DeliveryTracking::STATUS_RETURNED,
            'delivery_notes' => $reason
                ? ($delivery->delivery_notes . "\n[Returned] {$reason}")
                : $delivery->delivery_notes,
        ]);

        $delivery->addTrackingEvent('Returned to sender', ['reason' => $reason]);
        $delivery->save();

        $this->audit($delivery, 'returned', ['reason' => $reason]);

        return $delivery;
    }

    public function getActiveDeliveries(PurchaseOrder $po): array
    {
        return $po->deliveries()
            ->whereNotIn('status', [DeliveryTracking::STATUS_DELIVERED, DeliveryTracking::STATUS_RETURNED])
            ->orderByDesc('dispatched_date')
            ->get()
            ->all();
    }

    public function getTimeline(DeliveryTracking $delivery): array
    {
        return $delivery->tracking_events ?? [];
    }

    private function audit(DeliveryTracking $delivery, string $action, array $meta = []): void
    {
        AuditTrail::create([
            'actor_id' => auth()->id(),
            'action' => "delivery.{$action}",
            'entity_type' => DeliveryTracking::class,
            'entity_id' => $delivery->id,
            'metadata' => array_merge([
                'tracking_number' => $delivery->tracking_number,
                'status' => $delivery->status,
            ], $meta),
        ]);
    }
}