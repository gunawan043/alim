<?php

namespace App\Domain\Listeners;

use App\Domain\Events\BoardingPermitDecided;
use App\Services\DormitoryService;

class NotifyMahromOnPermitDecision
{
    public function __construct(private DormitoryService $service) {}

    public function handle(BoardingPermitDecided $event): void
    {
        if ($event->decision !== BoardingPermitDecided::APPROVED) {
            return;
        }

        $this->service->notifyMahromOnPermitApproval($event->permit);
    }
}
