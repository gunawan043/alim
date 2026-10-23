<?php

namespace App\Console\Commands;

use App\Models\DormitoryPermit;
use App\Models\DormitoryQrScanLog;
use App\Models\DormitoryVisitLog;
use Illuminate\Console\Command;

class PurgeDormitoryRetention extends Command
{
    protected $signature = 'dormitory:purge-retention {--dry-run : Tampilkan jumlah tanpa mengubah data}';

    protected $description = 'Anonimkan data izin/kunjungan lama dan hapus audit QR sesuai kebijakan retensi.';

    public function handle(): int
    {
        $visitMonths = max(1, (int) config('asrama.retention.visit_months', 24));
        $permitMonths = max(1, (int) config('asrama.retention.permit_months', 36));
        $qrDays = max(30, (int) config('asrama.retention.qr_scan_days', 365));
        $dryRun = (bool) $this->option('dry-run');

        $visitCutoff = now()->subMonths($visitMonths);
        $permitCutoff = now()->subMonths($permitMonths);
        $qrCutoff = now()->subDays($qrDays);

        $visits = DormitoryVisitLog::query()
            ->where(function ($q) use ($visitCutoff) {
                $q->where('check_out_at', '<', $visitCutoff)
                    ->orWhere(fn ($q2) => $q2->whereNull('check_out_at')->where('created_at', '<', $visitCutoff));
            })
            ->count();

        $permits = DormitoryPermit::query()
            ->where('created_at', '<', $permitCutoff)
            ->where(function ($q) {
                $q->where('destination', '!=', '[ANONIM]')
                    ->orWhere('companion_name', '!=', '[ANONIM]')
                    ->orWhereNotNull('emergency_contact_name');
            })
            ->count();

        $qrLogs = DormitoryQrScanLog::where('created_at', '<', $qrCutoff)->count();

        $this->table(['Data', 'Kandidat'], [
            ['Kunjungan dianonimkan', $visits],
            ['Izin dianonimkan', $permits],
            ['Audit QR dihapus', $qrLogs],
        ]);

        if ($dryRun) {
            $this->warn('Mode dry-run: tidak ada data yang diubah.');

            return self::SUCCESS;
        }

        DormitoryVisitLog::query()
            ->where(function ($q) use ($visitCutoff) {
                $q->where('check_out_at', '<', $visitCutoff)
                    ->orWhere(fn ($q2) => $q2->whereNull('check_out_at')->where('created_at', '<', $visitCutoff));
            })
            ->chunkById(200, function ($rows) {
                foreach ($rows as $visit) {
                    $visit->forceFill([
                        'visitor_name' => '[ANONIM]',
                        'visitor_id_number' => null,
                        'visitor_phone' => null,
                        'notes' => null,
                    ])->saveQuietly();
                }
            });

        DormitoryPermit::query()
            ->where('created_at', '<', $permitCutoff)
            ->chunkById(200, function ($rows) {
                foreach ($rows as $permit) {
                    $permit->forceFill([
                        'destination' => '[ANONIM]',
                        'purpose' => null,
                        'companion_name' => '[ANONIM]',
                        'companion_phone' => null,
                        'emergency_contact_name' => null,
                        'emergency_contact_phone' => null,
                    ])->saveQuietly();
                }
            });

        DormitoryQrScanLog::where('created_at', '<', $qrCutoff)->delete();

        \Log::info('[ASRAMA RETENTION] Data lama dianonimkan.', [
            'visits' => $visits,
            'permits' => $permits,
            'qr_logs' => $qrLogs,
        ]);

        $this->info('Retensi selesai.');

        return self::SUCCESS;
    }
}
