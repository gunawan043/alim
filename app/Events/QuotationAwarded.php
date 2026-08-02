<?php

namespace App\Events;

use App\Models\PurchaseOrder;
use App\Models\Quotation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuotationAwarded
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public PurchaseOrder $purchaseOrder,
    ) {}
}
