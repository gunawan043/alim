<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $userId;
    public array $notification;

    public function __construct(string $userId, array $notification)
    {
        $this->userId = $userId;
        $this->notification = $notification;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.received';
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->notification['id'] ?? null,
            'module'       => $this->notification['module'] ?? 'system',
            'type'         => $this->notification['type'] ?? 'info',
            'priority'     => $this->notification['priority'] ?? 'medium',
            'title'        => $this->notification['title'] ?? 'Notifikasi baru',
            'message'      => $this->notification['message'] ?? '',
            'action_url'   => $this->notification['action_url'] ?? null,
            'action_text'  => $this->notification['action_text'] ?? 'Lihat Detail',
            'created_at'   => now()->toIso8601String(),
        ];
    }
}
