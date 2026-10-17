<?php

namespace App\Jobs\Sarpras;

use App\Models\Asset;
use App\Services\Sarpras\RepairVsReplaceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecomputeRepairVsReplaceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public ?int $schoolId = null) {}

    public function handle(RepairVsReplaceService $rvr): void
    {
        $query = Asset::query();
        if ($this->schoolId) {
            $query->where('school_id', $this->schoolId);
        }

        $count = 0;
        $query->chunkById(50, function ($assets) use ($rvr, &$count) {
            foreach ($assets as $asset) {
                try {
                    $rvr->evaluate($asset);
                    $count++;
                } catch (\Throwable $e) {
                    Log::warning('RecomputeRepairVsReplaceJob failed', [
                        'asset_id' => $asset->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        Log::info('RecomputeRepairVsReplaceJob finished', [
            'school_id' => $this->schoolId,
            'processed' => $count,
        ]);
    }
}
