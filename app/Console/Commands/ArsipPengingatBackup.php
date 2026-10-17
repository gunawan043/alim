<?php

namespace App\Console\Commands;

use App\Services\Arsip\BackupReminderService;
use Illuminate\Console\Command;

/**
 * Pengingat backup arsip (database + storage/app/arsip).
 * Dijadwalkan mingguan; gunakan --notify untuk mengirim notifikasi ke Super Admin.
 */
class ArsipPengingatBackup extends Command
{
    protected $signature = 'arsip:pengingat-backup
                            {--path= : Direktori backup (default dari config)}
                            {--notify : Kirim notifikasi ke Super Admin bila backup kedaluwarsa}';

    protected $description = 'Periksa kebaruan backup arsip dan beri pengingat bila sudah kedaluwarsa.';

    public function handle(BackupReminderService $service): int
    {
        $path = $this->option('path') ?: (string) config('arsip.backup.path');
        $maxAge = max(1, (int) config('arsip.backup.max_age_days', 7));

        $latest = $service->latestBackupAt($path ?: null);

        if ($latest) {
            $this->info("Backup terbaru: {$latest->format('d M Y H:i')} ({$path})");
        } else {
            $this->warn("Tidak ditemukan berkas backup pada: {$path}");
        }

        if (! $service->isDue($path ?: null)) {
            $this->info("Backup masih dalam batas {$maxAge} hari.");

            return self::SUCCESS;
        }

        $this->warn("Backup arsip kedaluwarsa (>{$maxAge} hari). Backup database & folder storage/app/arsip.");

        if ($this->option('notify')) {
            $sent = $service->notifyAdmins($path ?: null);
            $this->info("Notifikasi dikirim ke {$sent} Super Admin.");
        }

        return self::SUCCESS;
    }
}
