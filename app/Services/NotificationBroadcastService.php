<?php

namespace App\Services;

use App\Events\NotificationEvent;
use App\Models\NotificationUniversal;
use Illuminate\Support\Facades\Log;

/**
 * NotificationBroadcastService
 *
 * Service ini mengirim notifikasi ke:
 * 1. Database (NotificationUniversal) — untuk history
 * 2. Pusher (real-time)               — untuk notifikasi instant
 */
class NotificationBroadcastService
{
    protected NotificationUniversalService $notificationService;

    public function __construct(NotificationUniversalService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Kirim notifikasi: DB + Broadcast
     *
     * @param string $userId
     * @param array $data
     * @return NotificationUniversal
     */
    public function send(string $userId, array $data): NotificationUniversal
    {
        // 1. Simpan ke database (history notifikasi)
        $notification = $this->notificationService->send($userId, $data);

        // 2. Broadcast real-time via Pusher
        $this->broadcast($userId, $notification->toArray());

        return $notification;
    }

    /**
     * Kirim notifikasi ke banyak user
     *
     * @param array $userIds
     * @param array $data
     * @return array
     */
    public function sendToMany(array $userIds, array $data): array
    {
        // Simpan ke DB
        $saved = $this->notificationService->sendToMany($userIds, $data);

        // Broadcast ke semua user
        foreach ($userIds as $userId) {
            $this->broadcast($userId, $data);
        }

        return $saved;
    }

    /**
     * Kirim ke semua user dengan role tertentu
     *
     * @param string $roleName
     * @param array $data
     * @return array
     */
    public function sendToRole(string $roleName, array $data): array
    {
        $saved = $this->notificationService->sendToRole($roleName, $data);

        $userIds = \App\Authorization\Services\ApprovalRoleResolver::resolvePermission($roleName);
        foreach ($userIds as $perm) {
            $resolved = usersHavingPermission($perm);
            foreach ($resolved as $userId) {
                $this->broadcast((string) $userId, $data);
            }
        }

        return $saved;
    }

    /**
     * Broadcast event ke Pusher channel
     *
     * @param string $userId
     * @param array $notificationData
     */
    protected function broadcast(string $userId, array $notificationData): void
    {
        try {
            event(new NotificationEvent($userId, $notificationData));
        } catch (\Exception $e) {
            // Jangan fail-kan proses utama kalau broadcast error
            Log::error('[Pusher] Broadcast failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'notification' => $notificationData,
            ]);
        }
    }

    /**
     * Helper: notifikasi persetujuan
     */
    public function notifyApprovalNeeded(
        string $userId,
        string $approverName,
        string $gtkName,
        string $requestType,
        string $requestId,
        string $actionUrl
    ): NotificationUniversal {
        return $this->send($userId, [
            'module'       => 'approval',
            'type'         => 'warning',
            'priority'     => 'high',
            'title'        => 'Persetujuan diperlukan',
            'message'      => "{$approverName} membutuhkan persetujuan Anda untuk {$requestType}: {$gtkName}",
            'action_url'   => $actionUrl,
            'reference_id' => $requestId,
            'action_text'  => 'Lihat Permintaan',
        ]);
    }

    /**
     * Helper: notifikasi data berhasil diupdate
     */
    public function notifyDataUpdated(
        string $userId,
        string $module,
        string $itemName,
        string $itemId,
        string $actionUrl
    ): NotificationUniversal {
        return $this->send($userId, [
            'module'       => $module,
            'type'         => 'success',
            'priority'     => 'medium',
            'title'        => 'Data berhasil diperbarui',
            'message'      => "Data {$module} \"{$itemName}\" telah berhasil diperbarui.",
            'action_url'   => $actionUrl,
            'reference_id' => $itemId,
            'action_text'  => 'Lihat Detail',
        ]);
    }

    /**
     * Helper: notifikasi persetujuan disetujui
     */
    public function notifyApproved(
        string $userId,
        string $approverName,
        string $gtkName,
        string $requestType,
        string $requestId,
        string $actionUrl
    ): NotificationUniversal {
        return $this->send($userId, [
            'module'       => 'approval',
            'type'         => 'success',
            'priority'     => 'medium',
            'title'        => 'Disetujui',
            'message'      => "{$approverName} telah menyetujui {$requestType} Anda: {$gtkName}",
            'action_url'   => $actionUrl,
            'reference_id' => $requestId,
            'action_text'  => 'Lihat Detail',
        ]);
    }

    /**
     * Helper: notifikasi persetujuan ditolak
     */
    public function notifyRejected(
        string $userId,
        string $approverName,
        string $gtkName,
        string $requestType,
        string $requestId,
        string $reason,
        string $actionUrl
    ): NotificationUniversal {
        return $this->send($userId, [
            'module'       => 'approval',
            'type'         => 'error',
            'priority'     => 'medium',
            'title'        => 'Ditolak',
            'message'      => "{$approverName} menolak {$requestType} Anda: {$gtkName}. Alasan: {$reason}",
            'action_url'   => $actionUrl,
            'reference_id' => $requestId,
            'action_text'  => 'Lihat Detail',
        ]);
    }

    /**
     * Helper: notifikasi umum (custom)
     */
    public function notify(
        string $userId,
        string $title,
        string $message,
        string $type = 'info',
        string $module = 'system',
        ?string $actionUrl = null,
        string $priority = 'medium'
    ): NotificationUniversal {
        return $this->send($userId, [
            'module'    => $module,
            'type'      => $type,
            'priority'  => $priority,
            'title'     => $title,
            'message'   => $message,
            'action_url'=> $actionUrl,
        ]);
    }
}
