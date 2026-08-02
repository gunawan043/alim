<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

readonly class VendorRatingDto implements Arrayable
{
    public function __construct(
        public int $vendorId,
        public int $ratedBy,
        public string $period,
        public float $score,
        public array $metrics = [],
        public ?string $remarks = null,
    ) {}

    public function toArray(): array
    {
        return [
            'vendor_id' => $this->vendorId,
            'rated_by' => $this->ratedBy,
            'period' => $this->period,
            'score' => $this->score,
            'metrics' => $this->metrics,
            'remarks' => $this->remarks,
        ];
    }
}
