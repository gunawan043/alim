<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'contact_person' => $this->contact_person,
            'category' => $this->category,
            'status' => $this->status,
            'rating' => $this->whenLoaded('performance', fn () => $this->performance->score ?? null),
            'address' => $this->when($request->boolean('with_address'), fn () => [
                'line1' => $this->address_line1,
                'line2' => $this->address_line2,
                'city' => $this->city,
                'province' => $this->province,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
