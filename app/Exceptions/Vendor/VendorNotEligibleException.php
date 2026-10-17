<?php

namespace App\Exceptions\Vendor;

use RuntimeException;

class VendorNotEligibleException extends RuntimeException
{
    public function __construct(
        public readonly int $vendorId,
        public readonly string $reason,
        ?string $context = null,
    ) {
        $message = "Vendor {$vendorId} is not eligible: {$reason}";
        if ($context) {
            $message .= " ({$context})";
        }

        parent::__construct($message, 422);
    }
}
