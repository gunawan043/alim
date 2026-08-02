<?php

namespace App\Exceptions\Vendor;

use RuntimeException;

class InvoiceApprovalConflictException extends RuntimeException
{
    public function __construct(
        public readonly int $invoiceId,
        public readonly string $conflictReason,
    ) {
        parent::__construct(
            "Invoice {$invoiceId} has an approval conflict: {$conflictReason}",
            409,
        );
    }
}
