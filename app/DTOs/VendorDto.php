<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

readonly class VendorDto implements Arrayable
{
    public function __construct(
        public string $name,
        public string $code,
        public string $email,
        public string $phone,
        public string $contactPerson = '',
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $province = null,
        public ?string $postalCode = null,
        public ?string $country = null,
        public string $category = 'general',
        public string $status = 'active',
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'email' => $this->email,
            'phone' => $this->phone,
            'contact_person' => $this->contactPerson,
            'address_line1' => $this->addressLine1,
            'address_line2' => $this->addressLine2,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'category' => $this->category,
            'status' => $this->status,
            'metadata' => $this->metadata,
        ];
    }
}
