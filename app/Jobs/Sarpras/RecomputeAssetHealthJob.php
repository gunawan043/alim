<?php

namespace App\Jobs\Sarpras;

use App\Models\Asset;
use App\Services\Sarpras\Automation\AssetHealthService;
use App\Services\Sarpras\Automation\CriticalityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecomputeAssetHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public ?int $schoolId = null, public int $chunkSize = 50) {}

    public function handle(AssetHealthService $health, CriticalityService $criticality): void
    {
        $query = Asset::query();
        if ($this->schoolId) {
            $query->where('school_id', $this->schoolId);
        }

        $count = 0;
        $query->chunkById($this->chunkSize, function ($assets) use ($health, $criticality, &$count) {
            foreach ($assets as $asset) {
                try {
                    $health->recompute($asset);
                    $criticality->recompute($asset);
                    $count++;
                } catch (\Throwable $e) {
                    Log::warning('RecomputeAssetHealthJob failed for asset', [
                        'asset_id' => $asset->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        Log::info('RecomputeAssetHealthJob finished', [
            'school_id' => $this->schoolId,
            'processed' => $count,
        ]);
    }
}
