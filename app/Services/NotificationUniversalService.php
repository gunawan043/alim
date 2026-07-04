<?php

namespace App\Services;

use App\Models\NotificationUniversal;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationUniversalService
{
    /**
     * Available modules in system
     */
    const MODULES = [
        'recruitment',
        'gtk',
        'work_unit',
        'career',
        'approval',
        'system',
        'transfer',
        'education',
        'competency',
        'training'
    ];

    /**
     * Send notification to single user
     */
    public function send($userId, array $data)
    {
        $notification = NotificationUniversal::create([
            'user_id' => $userId,
            'module' => $data['module'] ?? 'system',
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'reference_code' => $data['reference_code'] ?? null,
            'type' => $data['type'] ?? 'info',
            'action' => $data['action'] ?? 'system',
            'title' => $data['title'],
            'message' => $data['message'],
            'data' => $data['data'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'action_text' => $data['action_text'] ?? 'Lihat Detail',
            'priority' => $data['priority'] ?? 'medium',
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        // Handle delivery channels
        $this->handleDeliveryChannels($notification, $data);

        return $notification;
    }

    /**
     * Send notification to multiple users
     */
    public function sendToMany($userIds, array $data)
    {
        $notifications = [];
        $chunks = array_chunk((array)$userIds, 100); // Batch insert

        foreach ($chunks as $chunkUserIds) {
            $insertData = [];
            foreach ($chunkUserIds as $userId) {
                $insertData[] = [
                    'id' => Str::uuid(),
                    'user_id' => $userId,
                    'module' => $data['module'] ?? 'system',
                    'reference_type' => $data['reference_type'] ?? null,
                    'reference_id' => $data['reference_id'] ?? null,
                    'reference_code' => $data['reference_code'] ?? null,
                    'type' => $data['type'] ?? 'info',
                    'action' => $data['action'] ?? 'system',
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'data' => json_encode($data['data'] ?? null),
                    'action_url' => $data['action_url'] ?? null,
                    'action_text' => $data['action_text'] ?? 'Lihat Detail',
                    'priority' => $data['priority'] ?? 'medium',
                    'expires_at' => $data['expires_at'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            NotificationUniversal::insert($insertData);
            $notifications = array_merge($notifications, $insertData);
        }

        return $notifications;
    }

    /**
     * Send notification to users by role (snapshot-aware via PositionRoleMap).
     *
     * Looks up the role name in ApprovalRoleResolver / RoleToPermissionMapper
     * and resolves the snapshot permission. Falls back to direct Spatie role
     * query only if no permission mapping exists (legacy callers).
     */
    public function sendToRole($roleName, array $data)
    {
        $permissions = \App\Authorization\Services\ApprovalRoleResolver::resolvePermission($roleName);
        $userIds = [];
        foreach ($permissions as $permission) {
            $userIds = array_merge(
                $userIds,
                usersHavingPermission($permission)
            );
        }
        $userIds = array_values(array_unique($userIds));
        if (empty($userIds)) {
            // FALLBACK: direct role lookup (legacy — deprecated, kept for fail-safe only)
            $userIds = \App\Models\User::role($roleName)->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        }
        return $this->sendToMany($userIds, $data);
    }

    /**
     * Send notification to users by permission (snapshot-aware).
     */
    public function sendToPermission($permissionName, array $data)
    {
        $userIds = usersHavingPermission($permissionName);
        if (empty($userIds)) {
            // Fallback to spatie permission table (legacy)
            $userIds = User::permission($permissionName)->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        }
        return $this->sendToMany($userIds, $data);
    }

    /**
     * Handle delivery channels (email, whatsapp, push)
     */
    protected function handleDeliveryChannels($notification, $data)
    {
        $user = User::find($notification->user_id);
        if (!$user) return;

        // Email
        if (($data['send_email'] ?? false) && $user->email) {
            $this->sendEmail($user, $notification);
        }

        // WhatsApp
        if (($data['send_whatsapp'] ?? false)) {
            $phone = $user->gtkProfiles?->no_whatsapp ?? $user->recruitmentProfile?->no_whatsapp;
            if ($phone) {
                $this->sendWhatsApp($user, $notification, $phone);
            }
        }

        // Push Notification (Web)
        if (($data['send_push'] ?? false)) {
            $this->sendPushNotification($user, $notification);
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmail($user, $notification)
    {
        try {
            // Queue email job
            \App\Jobs\SendUniversalEmail::dispatch($user, $notification);
            
            $notification->update([
                'is_email_sent' => true,
                'email_sent_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email notification: ' . $e->getMessage());
        }
    }

    /**
     * Send WhatsApp notification
     */
    protected function sendWhatsApp($user, $notification, $phone)
    {
        try {
            // Queue WhatsApp job
            \App\Jobs\SendUniversalWhatsApp::dispatch($user, $notification, $phone);
            
            $notification->update([
                'is_whatsapp_sent' => true,
                'whatsapp_sent_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification: ' . $e->getMessage());
        }
    }

    /**
     * Send push notification
     */
    protected function sendPushNotification($user, $notification)
    {
        try {
            // Queue push notification job
            \App\Jobs\SendUniversalPush::dispatch($user, $notification);
            
            $notification->update([
                'is_push_sent' => true,
                'push_sent_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send push notification: ' . $e->getMessage());
        }
    }

    /**
     * Mark as read
     */
    public function markAsRead($notificationId, $userId)
    {
        $notification = NotificationUniversal::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($notification && !$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        }

        return $notification;
    }

    /**
     * Mark all as read for user
     */
    public function markAllAsRead($userId)
    {
        return NotificationUniversal::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
    }

    /**
     * Archive notification
     */
    public function archive($notificationId, $userId)
    {
        $notification = NotificationUniversal::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($notification) {
            $notification->update([
                'is_archived' => true,
                'archived_at' => now()
            ]);
        }

        return $notification;
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount($userId)
    {
        return NotificationUniversal::where('user_id', $userId)
            ->where('is_read', false)
            ->where('is_archived', false)
            ->notExpired()
            ->count();
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $filters = [])
    {
        $query = NotificationUniversal::where('user_id', $userId)
            ->notArchived()
            ->notExpired();

        if (!empty($filters['module'])) {
            $query->byModule($filters['module']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['is_read'])) {
            $filters['is_read'] == 'true' ? $query->read() : $query->unread();
        }

        if (!empty($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Clean up old notifications
     */
    public function cleanup($days = 30)
    {
        return NotificationUniversal::where('created_at', '<', now()->subDays($days))
            ->where('is_archived', true)
            ->forceDelete();
    }
}