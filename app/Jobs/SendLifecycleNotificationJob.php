<?php

namespace App\Jobs;

use App\Services\NotificationUniversalService;
use App\Support\LifecycleMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendLifecycleNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly string $userId,
        public readonly LifecycleMessage $message,
        public readonly array $context = [],
    ) {}

    public function handle(NotificationUniversalService $notifier): void
    {
        try {
            $notifier->send(
                $this->userId,
                [
                    'module' => 'student_lifecycle',
                    'type' => $this->message->priority,
                    'title' => $this->message->title,
                    'message' => $this->message->body,
                    'action_url' => $this->message->actionUrl,
                    'action_text' => $this->message->actionText,
                    'data' => $this->context,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('SendLifecycleNotificationJob failed', [
                'user_id' => $this->userId,
                'event' => $this->context['event'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendLifecycleNotificationJob permanently failed', [
            'user_id' => $this->userId,
            'error' => $e->getMessage(),
        ]);
    }
}
