<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RfqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rfq_number' => $this->rfq_number,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'deadline' => $this->quotation_deadline?->toIso8601String(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'quotation_count' => $this->whenCounted('quotations'),
            'invited_vendor_count' => $this->whenCounted('invitations'),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'id' => $i->id,
                'item_name' => $i->item_name,
                'specification' => $i->specification,
                'quantity' => $i->quantity,
                'unit' => $i->unit,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
        ];
    }
}
