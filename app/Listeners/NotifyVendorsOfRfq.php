<?php

namespace App\Listeners;

use App\Events\RfqPublished;
use App\Models\Vendor;
use App\Services\Vendor\NotificationService;

class NotifyVendorsOfRfq
{
    public function __construct(protected NotificationService $notification) {}

    public function handle(RfqPublished $event): void
    {
        $rfq = $event->rfq;

        $invitedVendors = $rfq->invitations
            ->filter(fn ($inv) => $inv->status === 'invited')
            ->pluck('vendor');

        if ($invitedVendors->isEmpty()) {
            $invitedVendors = Vendor::all();
        }

        $invitedVendors->each(function (Vendor $vendor) use ($rfq) {
            $this->notification->send(
                $vendor,
                "RFQ #{$rfq->rfq_number} Published: {$rfq->title}",
                'A new Request for Quotation has been published. Please review and submit your quotation before the deadline.',
                [
                    'rfq_id' => $rfq->id,
                    'rfq_number' => $rfq->rfq_number,
                    'deadline' => $rfq->quotation_deadline->format('Y-m-d H:i:s'),
                    'route' => '/vendor/rfq/'.$rfq->id,
                ]
            );
        });
    }
}
