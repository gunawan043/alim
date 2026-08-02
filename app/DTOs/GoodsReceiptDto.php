<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

readonly class GoodsReceiptDto implements Arrayable
{
    public function __construct(
        public int $purchaseOrderId,
        public int $warehouseId,
        public int $vendorId,
        public string $receivedDate,
        public string $receivedBy,
        public array $items = [],
        public ?string $deliveryNoteNumber = null,
        public ?string $notes = null,
    ) {}

    public function toArray(): array
    {
        return [
            'purchase_order_id' => $this->purchaseOrderId,
            'warehouse_id' => $this->warehouseId,
            'vendor_id' => $this->vendorId,
            'received_date' => $this->receivedDate,
            'received_by' => $this->receivedBy,
            'items' => $this->items,
            'delivery_note_number' => $this->deliveryNoteNumber,
            'notes' => $this->notes,
        ];
    }
}
