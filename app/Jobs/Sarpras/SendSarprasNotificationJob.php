<?php

namespace App\Jobs\Sarpras;

use App\Models\User;
use App\Services\Sarpras\Automation\SarprasNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSarprasNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly array $recipientUserIds,
        public readonly array $payload,
    ) {}

    public function handle(SarprasNotificationService $service): void
    {
        foreach ($this->recipientUserIds as $uid) {
            $service->deliver((int) $uid, $this->payload);
        }
    }
}