<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'po_number' => $this->po_number,
            'vendor' => $this->whenLoaded('vendor', fn () => [
                'id' => $this->vendor->id,
                'name' => $this->vendor->name,
                'code' => $this->vendor->code,
            ]),
            'quotation_id' => $this->quotation_id,
            'status' => $this->status,
            'qc_status' => $this->qc_status,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'tracking_number' => $this->tracking_number,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'id' => $i->id,
                'item_name' => $i->item_name,
                'quantity' => $i->quantity,
                'unit_price' => $i->unit_price,
                'total' => $i->total,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
        ];
    }
}
