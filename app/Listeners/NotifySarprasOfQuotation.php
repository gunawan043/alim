<?php

namespace App\Listeners;

use App\Events\QuotationSubmitted;
use App\Models\User;
use App\Services\Vendor\NotificationService;

class NotifySarprasOfQuotation
{
    public function __construct(protected NotificationService $notification) {}

    public function handle(QuotationSubmitted $event): void
    {
        $quotation = $event->quotation;

        $sarprasUsers = User::whereHas('roles', function ($q) {
            $q->where('name', 'sarpras');
        })->get();

        $sarprasUsers->each(function (User $user) use ($quotation) {
            $this->notification->send(
                $user,
                "Quotation Submitted: {$quotation->quotation_number}",
                "Vendor submitted quotation #{$quotation->quotation_number} for RFQ #{$quotation->rfq->rfq_number}. Total: {$quotation->currency} {$quotation->total}",
                [
                    'quotation_id' => $quotation->id,
                    'rfq_id' => $quotation->rfq_id,
                    'route' => '/sarpras/quotations/'.$quotation->id,
                ]
            );
        });
    }
}
