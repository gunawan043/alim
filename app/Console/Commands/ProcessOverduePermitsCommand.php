<?php

namespace App\Console\Commands;

use App\Services\DormitoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessOverduePermitsCommand extends Command
{
    protected $signature = 'dormitory:process-overdue
                            {--dry-run : Hanya tampilkan hasil tanpa mengirim notifikasi}';

    protected $description = 'Proses permit asrama yang overdue — kirim reminder & eskalasi';

    protected DormitoryService $service;

    public function __construct(DormitoryService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  Mode DRY RUN — tidak ada notifikasi yang akan dikirim.');
        }

        $this->info('⏰ Memproses permit asrama yang overdue...');

        if ($dryRun) {
            $this->info('✅ Selesai (dry-run).');

            return 0;
        }

        try {
            $count = $this->service->processOverduePermits();
            $this->info("✅ Selesai. {$count} permit diproses.");
            Log::info("ProcessOverduePermits: {$count} permits processed.");

            return 0;
        } catch (\Throwable $e) {
            $this->error('❌ Gagal: '.$e->getMessage());
            Log::error('ProcessOverduePermits failed: '.$e->getMessage());

            return 1;
        }
    }
}
