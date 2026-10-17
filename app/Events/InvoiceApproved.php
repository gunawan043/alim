<?php

namespace App\Events;

use App\Models\InvoiceApproval;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceApproved
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public InvoiceApproval $invoice) {}
}
