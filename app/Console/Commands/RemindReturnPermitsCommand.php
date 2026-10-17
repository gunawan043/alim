<?php

namespace App\Console\Commands;

use App\Models\DormitoryPermit;
use App\Services\Notifications\GuardianWhatsAppNotifier;
use Illuminate\Console\Command;

/**
 * Pengingat H-1 sebelum batas kembali izin asrama (WA wali + in-app petugas).
 */
class RemindReturnPermitsCommand extends Command
{
    protected $signature = 'dormitory:remind-returns';

    protected $description = 'Kirim pengingat H-1 kepada wali untuk izin yang harus kembali besok';

    public function handle(GuardianWhatsAppNotifier $notifier): int
    {
        $tomorrowStart = now()->addDay()->startOfDay();
        $tomorrowEnd = now()->addDay()->endOfDay();

        $permits = DormitoryPermit::with(['student', 'dormitory'])
            ->whereIn('status', ['approved', 'picked_up'])
            ->whereBetween('expected_return_datetime', [$tomorrowStart, $tomorrowEnd])
            ->get();

        $sent = 0;

        foreach ($permits as $permit) {
            try {
                if ($notifier->notifyReturnReminder($permit)) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Reminder H-1 gagal: '.$e->getMessage());
            }
        }

        $this->info("Pengingat H-1 terkirim untuk {$sent} dari {$permits->count()} izin.");

        return self::SUCCESS;
    }
}
