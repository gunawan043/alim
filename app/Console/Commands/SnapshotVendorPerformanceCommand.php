<?php

namespace App\Console\Commands;

use App\Services\Sarpras\VendorPerformanceService;
use Illuminate\Console\Command;

class SnapshotVendorPerformanceCommand extends Command
{
    protected $signature = 'sarpras:snapshot-vendor-performance';

    protected $description = 'Snapshot daily vendor performance metrics';

    public function handle(VendorPerformanceService $service): int
    {
        $count = $service->snapshotPerformance();
        $this->info("Snapshotted {$count} vendor performance records.");

        return self::SUCCESS;
    }
}
