<?php

namespace App\Exceptions\Vendor;

use RuntimeException;

class RfqAlreadyClosedException extends RuntimeException
{
    public function __construct(public readonly int $rfqId, public readonly string $deadline)
    {
        parent::__construct(
            "RFQ #{$rfqId} closed on {$deadline} — no further quotations accepted.",
            409,
        );
    }
}
