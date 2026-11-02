<?php

namespace App\Console\Commands;

use App\Services\Dormitory\ReturnReminderService;
use Illuminate\Console\Command;

/**
 * Pengingat H-1 sebelum batas kembali izin asrama (WA wali + kanal lain).
 * Dijadwalkan setiap menit; jam kirim mengikuti pengaturan tiap asrama.
 */
class RemindReturnPermitsCommand extends Command
{
    protected $signature = 'dormitory:remind-returns';

    protected $description = 'Kirim pengingat H-1 kepada wali untuk izin yang harus kembali besok';

    public function handle(ReturnReminderService $reminders): int
    {
        $sent = $reminders->processHeadStartReminders();

        if ($sent > 0) {
            $this->info("Pengingat H-1 terkirim untuk {$sent} izin.");
        }

        return self::SUCCESS;
    }
}
