<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

readonly class QualityCheckDto implements Arrayable
{
    public function __construct(
        public int $goodsReceiptId,
        public int $purchaseOrderId,
        public int $inspectorId,
        public string $inspectedAt,
        public string $outcome,
        public array $findings = [],
        public ?string $notes = null,
    ) {}

    public function toArray(): array
    {
        return [
            'goods_receipt_id' => $this->goodsReceiptId,
            'purchase_order_id' => $this->purchaseOrderId,
            'inspector_id' => $this->inspectorId,
            'inspected_at' => $this->inspectedAt,
            'outcome' => $this->outcome,
            'findings' => $this->findings,
            'notes' => $this->notes,
        ];
    }
}
