<?php

namespace App\Jobs;

use App\Models\QualityCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PerformQualityCheckJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $qualityCheckId
    ) {}

    public function handle(): void
    {
        $qc = QualityCheck::with('items')->find($this->qualityCheckId);
        if (! $qc) {
            return;
        }

        // Auto-run additional QC checks
        $service = app(\App\Services\QualityCheckService::class);

        // Trigger any pending chemical / physical lab tests
        foreach ($qc->items as $item) {
            if ($item->requires_lab_test ?? false) {
                // Defer to lab queue
                \App\Jobs\LabTestJob::dispatch($item->id);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Log
    }
}
