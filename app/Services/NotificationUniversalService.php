<?php

namespace App\Services;

use App\Authorization\Services\ApprovalRoleResolver;
use App\Jobs\SendUniversalEmail;
use App\Jobs\SendUniversalPush;
use App\Jobs\SendUniversalWhatsApp;
use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\NotificationPreference;
use App\Models\NotificationUniversal;
use App\Models\User;
use App\Models\WaliSantri;
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
        'training',
        'osipp',
    ];

    /**
     * Send notification to single user
     */
    public function send($userId, array $data)
    {
        $action = (string) ($data['action'] ?? 'system');
        $systemEnabled = $this->channelAllowed($action, 'system');

        $notification = NotificationUniversal::create([
            'user_id' => $userId,
            'work_unit_id' => $data['work_unit_id'] ?? null,
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
            // Jika channel Sistem dimatikan, notifikasi tetap disimpan untuk
            // audit/channel lain tetapi diarsipkan agar tidak tampil di lonceng.
            'is_archived' => ! $systemEnabled,
            'archived_at' => $systemEnabled ? null : now(),
        ]);

        // Handle delivery channels
        $this->handleDeliveryChannels($notification, $data);

        return $notification;
    }

    /**
     * Send notification to multiple users
     */
    /**
     * Kirim notifikasi ke seluruh wali aktif dari sekumpulan santri.
     * Dipakai saat rapor/nilai dipublikasikan (in-app web + API mobile wali).
     *
     * @param  iterable<string>  $studentIds
     */
    public function notifyGuardiansForStudents(iterable $studentIds, array $data): int
    {
        $ids = collect($studentIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $links = WaliSantri::with('student')
            ->whereIn('student_id', $ids)
            ->active()
            ->get();

        $count = 0;
        foreach ($links as $link) {
            $payload = $data;
            $payload['data'] = array_merge($payload['data'] ?? [], [
                'student_id' => $link->student_id,
                'student_name' => $link->student?->name,
            ]);

            if ($link->access_token) {
                $payload['action_url'] = route('portal.dashboard', $link->access_token);
            }

            $this->send($link->user_id, $payload);
            $count++;
        }

        return $count;
    }

    /**
     * Kirim ke seluruh pengguna pada satu SATUAN KERJA (work unit).
     */
    /**
     * ID pengguna pada satu satuan kerja — gabungan pivot gtk_work_unit
     * dan kepegawaian (gtk_employments.school_id → schools.work_unit_id).
     *
     * @return array<int, string>
     */
    public function userIdsForWorkUnit(string $workUnitId): array
    {
        $viaPivot = User::query()
            ->whereHas('gtkWorkUnits', fn ($q) => $q->where('work_unit_id', $workUnitId))
            ->pluck('id');

        $viaSchool = User::query()
            ->whereHas('employment.school', fn ($q) => $q->where('work_unit_id', $workUnitId))
            ->pluck('id');

        return $viaPivot->merge($viaSchool)->unique()->values()->all();
    }

    /**
     * Notifikasi petugas asrama (kepala + staf aktif) untuk satu asrama.
     */
    public function notifyDormitoryStaff(string $dormitoryId, array $data): int
    {
        $dormitory = Dormitory::withTrashed()->find($dormitoryId);

        if (! $dormitory) {
            return 0;
        }

        $ids = DormitoryStaffAssignment::query()
            ->where('dormitory_id', $dormitoryId)
            ->where('status', 'active')
            ->pluck('user_id');

        if ($dormitory->head_id) {
            $ids->push($dormitory->head_id);
        }

        return count($this->sendToMany($ids->unique()->values()->all(), $data));
    }

    /**
     * Notifikasi GTK generik: per satuan kerja, atau GLOBAL (semua pengguna
     * aktif) bila satuan kerja tidak diketahui — mis. dibuat Super Admin.
     */
    public function notifyGtk(?string $workUnitId, array $data, ?string $excludeUserId = null): int
    {
        if ($workUnitId) {
            $ids = $this->userIdsForWorkUnit($workUnitId);

            if ($excludeUserId) {
                $ids = array_values(array_filter($ids, fn ($id) => (string) $id !== (string) $excludeUserId));
            }

            return count($this->sendToMany($ids, $data + ['work_unit_id' => $workUnitId]));
        }

        $ids = User::query()->where('is_active', true)->pluck('id')->all();

        return count($this->sendToMany($ids, $data));
    }

    public function sendToWorkUnit(string $workUnitId, array $data)
    {
        $userIds = User::query()
            ->whereHas('gtkWorkUnits', fn ($q) => $q->where('work_unit_id', $workUnitId))
            ->pluck('id')
            ->all();

        return $this->sendToMany($userIds, $data + ['work_unit_id' => $workUnitId]);
    }

    public function sendToMany($userIds, array $data)
    {
        $notifications = [];
        $chunks = array_chunk((array) $userIds, 100); // Batch insert

        foreach ($chunks as $chunkUserIds) {
            $insertData = [];
            foreach ($chunkUserIds as $userId) {
                $insertData[] = [
                    'id' => Str::uuid(),
                    'user_id' => $userId,
                    'work_unit_id' => $data['work_unit_id'] ?? null,
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
        $permissions = ApprovalRoleResolver::resolvePermission($roleName);
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
            try {
                $userIds = User::role($roleName)->pluck('id')->map(fn ($id) => (string) $id)->toArray();
            } catch (\Throwable $e) {
                // Role tidak ditemukan — skip (contoh: role baru belum di-seed)
                Log::debug("[NotificationUniversal] Role not found, skipping: {$roleName}", [
                    'exception' => $e->getMessage(),
                ]);

                return [];
            }
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
        if (! $user) {
            return;
        }

        // Preferensi pengguna (default aktif semua bila belum diatur).
        $prefs = NotificationPreference::forUser($user);
        $action = (string) ($data['action'] ?? $notification->action ?? 'system');

        $emailEnabled = (string) setting('email_notifications', '1') !== '0';
        $whatsappEnabled = (string) setting('whatsapp_notifications', '1') !== '0';
        $pushEnabled = (string) setting('push_notifications', '1') !== '0';

        // Email
        if (($data['send_email'] ?? false) && $emailEnabled && $prefs->email_enabled
            && $this->channelAllowed($action, 'email') && $user->email) {
            $this->sendEmail($user, $notification);
        }

        // WhatsApp
        if (($data['send_whatsapp'] ?? false) && $whatsappEnabled && $prefs->whatsapp_enabled
            && $this->channelAllowed($action, 'wa')) {
            $phone = $this->recipientPhone($user);
            if ($phone) {
                $this->sendWhatsApp($user, $notification, $phone, $data);
            }
        }

        // Push Notification (Web)
        if (($data['send_push'] ?? false) && $pushEnabled && $prefs->push_enabled
            && $this->channelAllowed($action, 'push')) {
            $this->sendPushNotification($user, $notification);
        }
    }

    /**
     * Cek matriks channel global (Super Admin). Default: aktif.
     */
    private function channelAllowed(string $action, string $channel): bool
    {
        $raw = setting('notification_channel_matrix');

        if (! $raw) {
            return true;
        }

        $matrix = is_array($raw) ? $raw : json_decode((string) $raw, true);

        if (! is_array($matrix) || ! isset($matrix[$action])) {
            return true;
        }

        return (bool) ($matrix[$action][$channel] ?? true);
    }

    /**
     * Nomor WhatsApp penerima: kontak GTK → no_hp akun → profil → recruitment.
     */
    private function recipientPhone(User $user): ?string
    {
        return $user->gtkContact?->no_whatsapp
            ?: ($user->no_hp ?: null)
            ?: $user->gtkProfile?->no_whatsapp
            ?: $user->recruitmentProfile?->no_whatsapp;
    }

    /**
     * Send email notification
     */
    protected function sendEmail($user, $notification)
    {
        try {
            // Queue email job
            SendUniversalEmail::dispatch($user, $notification);

            $notification->update([
                'is_email_sent' => true,
                'email_sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email notification: '.$e->getMessage());
        }
    }

    /**
     * Send WhatsApp notification
     */
    protected function sendWhatsApp($user, $notification, $phone, array $data = [])
    {
        try {
            // Queue WhatsApp job (sertakan konteks untuk template Meta).
            SendUniversalWhatsApp::dispatch($user, $notification, $phone, [
                'event' => $data['action'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'template_params' => $data['template_params'] ?? [],
            ]);

            $notification->update([
                'is_whatsapp_sent' => true,
                'whatsapp_sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification: '.$e->getMessage());
        }
    }

    /**
     * Send push notification
     */
    protected function sendPushNotification($user, $notification)
    {
        try {
            // Queue push notification job
            SendUniversalPush::dispatch($user, $notification);

            $notification->update([
                'is_push_sent' => true,
                'push_sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send push notification: '.$e->getMessage());
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

        if ($notification && ! $notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
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
                'read_at' => now(),
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
                'archived_at' => now(),
            ]);
        }

        return $notification;
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount($userId, ?string $workUnitId = null)
    {
        return NotificationUniversal::where('user_id', $userId)
            ->when($workUnitId, fn ($q) => $q->where(fn ($qq) => $qq->whereNull('work_unit_id')->orWhere('work_unit_id', $workUnitId)))
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

        if (! empty($filters['module'])) {
            $query->byModule($filters['module']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['is_read'])) {
            $filters['is_read'] == 'true' ? $query->read() : $query->unread();
        }

        if (! empty($filters['priority'])) {
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
