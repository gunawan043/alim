<?php

namespace App\Events;

use App\Models\VendorNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendorNotificationDispatched
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public VendorNotification $notification) {}
}
