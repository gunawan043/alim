<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

readonly class RfqDto implements Arrayable
{
    public function __construct(
        public int $vendorId,
        public int $purchaseOrderId,
        public string $subject,
        public string $description,
        public string $channel,
        public int $deadlineDays = 7,
        public array $lineItems = [],
    ) {}

    public function toArray(): array
    {
        return [
            'vendor_id' => $this->vendorId,
            'purchase_order_id' => $this->purchaseOrderId,
            'subject' => $this->subject,
            'description' => $this->description,
            'channel' => $this->channel,
            'deadline_days' => $this->deadlineDays,
            'line_items' => $this->lineItems,
        ];
    }
}
