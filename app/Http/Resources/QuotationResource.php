<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quotation_number' => $this->quotation_number,
            'rfq_id' => $this->rfq_id,
            'vendor' => $this->whenLoaded('vendor', fn () => [
                'id' => $this->vendor->id,
                'name' => $this->vendor->name,
                'code' => $this->vendor->code,
            ]),
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'valid_until' => $this->valid_until?->toDateString(),
            'delivery_days' => $this->delivery_days,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'is_awarded' => $this->status === 'awarded',
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'id' => $i->id,
                'item_name' => $i->item_name,
                'unit_price' => $i->unit_price,
                'quantity' => $i->quantity,
                'total' => $i->total,
            ])),
        ];
    }
}
