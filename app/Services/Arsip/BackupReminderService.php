<?php

namespace App\Services\Arsip;

use App\Models\User;
use App\Services\NotificationUniversalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

/**
 * Pengingat backup arsip: memeriksa berkas backup terbaru pada direktori
 * terkonfigurasi dan mengirim notifikasi ke Super Admin bila sudah kedaluwarsa.
 */
class BackupReminderService
{
    public function latestBackupAt(?string $path = null): ?Carbon
    {
        $path = $path ?: (string) config('arsip.backup.path');

        if ($path === '' || ! is_dir($path)) {
            return null;
        }

        $latest = null;

        foreach (File::allFiles($path) as $file) {
            $mtime = $file->getMTime();

            if ($latest === null || $mtime > $latest) {
                $latest = $mtime;
            }
        }

        return $latest ? Carbon::createFromTimestamp($latest) : null;
    }

    public function isDue(?string $path = null): bool
    {
        $latest = $this->latestBackupAt($path);

        if (! $latest) {
            return true;
        }

        $maxAge = max(1, (int) config('arsip.backup.max_age_days', 7));

        return $latest->lt(now()->subDays($maxAge));
    }

    /**
     * Kirim notifikasi ke Super Admin/System Admin. Mengembalikan jumlah terkirim.
     */
    public function notifyAdmins(?string $path = null): int
    {
        $latest = $this->latestBackupAt($path);
        $maxAge = max(1, (int) config('arsip.backup.max_age_days', 7));

        $admins = User::query()
            ->where(fn ($q) => $q->where('is_system_admin', true)
                ->orWhereHas('roles', fn ($r) => $r->where('name', 'Super Admin')))
            ->where('is_active', true)
            ->get();

        $sent = 0;

        foreach ($admins as $admin) {
            try {
                app(NotificationUniversalService::class)->send($admin->id, [
                    'module' => 'arsip',
                    'reference_type' => null,
                    'reference_id' => null,
                    'type' => 'warning',
                    'action' => 'backup_reminder',
                    'priority' => 'high',
                    'title' => 'Pengingat Backup Arsip',
                    'message' => $latest
                        ? 'Backup terakhir '.$latest->format('d M Y H:i').' (lebih dari '.$maxAge.' hari). Segera backup database & folder storage/app/arsip.'
                        : 'Belum ditemukan berkas backup arsip. Segera backup database & folder storage/app/arsip.',
                    'work_unit_id' => null,
                    'action_url' => route('user.arsip.index', ['userId' => $admin->id]),
                    'action_text' => 'Buka Inventaris Arsip',
                ]);
                $sent++;
            } catch (\Throwable) {
                // Notifikasi tidak boleh menggagalkan command.
            }
        }

        return $sent;
    }
}
