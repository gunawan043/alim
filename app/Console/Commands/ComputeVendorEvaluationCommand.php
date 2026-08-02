<?php

namespace App\Console\Commands;

use App\Events\Sarpras\VendorEvaluationCompleted;
use App\Models\Vendor;
use App\Services\Sarpras\VendorPerformanceService;
use Illuminate\Console\Command;

class ComputeVendorEvaluationCommand extends Command
{
    protected $signature = 'sarpras:compute-vendor-evaluation {--month= : Period month (YYYY-MM)}';

    protected $description = 'Compute vendor evaluation for previous month';

    public function handle(VendorPerformanceService $service): int
    {
        $month = $this->option('month') ?: now()->subMonth()->format('Y-m');
        $start = $month.'-01';
        $end = date('Y-m-t', strtotime($start));

        $count = 0;
        Vendor::where('status', 'active')->each(function ($vendor) use ($service, $start, $end, &$count) {
            $eval = $service->saveEvaluation($vendor, $start, $end);
            event(new VendorEvaluationCompleted($vendor, $eval));
            $count++;
        });

        $this->info("Computed {$count} vendor evaluations for period {$start} to {$end}.");

        return self::SUCCESS;
    }
}
