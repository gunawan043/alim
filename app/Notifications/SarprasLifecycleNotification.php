<?php

namespace App\Notifications;

use App\Models\RepairRequest;
use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class SarprasLifecycleNotification extends Notification
{
    use Queueable;

    public function __construct(public $subject) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        if ($this->subject instanceof RepairRequest) {
            return new DatabaseMessage([
                'type' => 'sarpras.repair.status',
                'repair_id' => $this->subject->id,
                'request_number' => $this->subject->request_number,
                'status' => $this->subject->status,
                'title' => 'Status laporan: '.str_replace('_', ' ', $this->subject->status),
            ]);
        }

        if ($this->subject instanceof WorkOrder) {
            return new DatabaseMessage([
                'type' => 'sarpras.wo.created',
                'wo_id' => $this->subject->id,
                'wo_number' => $this->subject->wo_number,
                'title' => 'Work Order baru: '.$this->subject->wo_number,
            ]);
        }

        return new DatabaseMessage(['type' => 'sarpras.unknown']);
    }
}
