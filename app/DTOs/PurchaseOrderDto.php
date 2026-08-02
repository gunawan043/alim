<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

readonly class PurchaseOrderDto implements Arrayable
{
    public function __construct(
        public int $quotationId,
        public int $vendorId,
        public string $shippingMethod = 'standard',
        public string $specialInstructions = '',
        public array $items = [],
    ) {}

    public function toArray(): array
    {
        return [
            'quotation_id' => $this->quotationId,
            'vendor_id' => $this->vendorId,
            'shipping_method' => $this->shippingMethod,
            'special_instructions' => $this->specialInstructions,
            'items' => $this->items,
        ];
    }
}
