<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

readonly class QuotationDto implements Arrayable
{
    public function __construct(
        public int $rfqId,
        public int $vendorId,
        public string $subject,
        public float $totalAmount,
        public string $currency = 'IDR',
        public int $priceAdjustment = 0,
        public string $status = 'submitted',
        public array $items = [],
        public array $documents = [],
    ) {}

    public function toArray(): array
    {
        return [
            'rfq_id' => $this->rfqId,
            'vendor_id' => $this->vendorId,
            'subject' => $this->subject,
            'total_amount' => $this->totalAmount,
            'currency' => $this->currency,
            'price_adjustment' => $this->priceAdjustment,
            'status' => $this->status,
            'items' => $this->items,
            'documents' => $this->documents,
        ];
    }
}
