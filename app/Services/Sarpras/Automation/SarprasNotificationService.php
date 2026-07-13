<?php

namespace App\Services\Sarpras\Automation;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SarprasNotificationService
{
    /**
     * Build payload for a Sarpras event and dispatch asynchronously.
     */
    public function dispatch(
        string $eventType,
        array $recipientUserIds,
        string $title,
        string $message,
        array $context = [],
        string $priority = 'medium',
    ): void {
        if (empty($recipientUserIds)) {
            Log::info("SarprasNotification: no recipients for {$eventType}");
            return;
        }

        $payload = [
            'event_type' => $eventType,
            'module' => 'sarpras',
            'type' => $this->resolveType($eventType),
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'data' => $context,
            'reference_type' => $context['reference_type'] ?? null,
            'reference_id' => $context['reference_id'] ?? null,
            'reference_code' => $context['reference_code'] ?? null,
            'action_url' => $context['action_url'] ?? null,
            'action_text' => $context['action_text'] ?? 'Lihat Detail',
        ];

        \App\Jobs\Sarpras\SendSarprasNotificationJob::dispatch(
            array_map('intval', $recipientUserIds),
            $payload,
        );
    }

    /**
     * Direct delivery (synchronous — for caller who already has Service).
     */
    public function deliver(int $userId, array $payload): void
    {
        try {
            \App\Models\NotificationUniversal::create([
                'user_id' => $userId,
                'module' => $payload['module'] ?? 'sarpras',
                'reference_type' => $payload['reference_type'] ?? null,
                'reference_id' => $payload['reference_id'] ?? null,
                'reference_code' => $payload['reference_code'] ?? null,
                'type' => $payload['type'] ?? 'info',
                'action' => $payload['event_type'] ?? 'system',
                'title' => $payload['title'],
                'message' => $payload['message'],
                'data' => $payload['data'] ?? null,
                'action_url' => $payload['action_url'] ?? null,
                'action_text' => $payload['action_text'] ?? 'Lihat Detail',
                'priority' => $payload['priority'] ?? 'medium',
            ]);
        } catch (\Throwable $e) {
            Log::warning("SarprasNotification deliver failed for user {$userId}: " . $e->getMessage());
        }
    }

    protected function resolveType(string $eventType): string
    {
        if (str_contains($eventType, 'Overdue') || str_contains($eventType, 'Escalated') || str_contains($eventType, 'Expired')) {
            return 'danger';
        }
        if (str_contains($eventType, 'Warning')) {
            return 'warning';
        }
        if (str_contains($eventType, 'Completed') || str_contains($eventType, 'Approved')) {
            return 'success';
        }
        return 'info';
    }
}