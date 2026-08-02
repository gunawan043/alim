<?php

namespace App\Exceptions\Vendor;

use RuntimeException;

class GoodsReceiptMismatchException extends RuntimeException
{
    public function __construct(
        public readonly int $receiptId,
        public readonly int $poId,
        public readonly string $reason,
    ) {
        parent::__construct(
            "Goods receipt {$receiptId} does not match PO {$poId}: {$reason}",
            422,
        );
    }
}
