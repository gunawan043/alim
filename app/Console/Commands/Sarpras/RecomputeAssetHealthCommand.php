<?php

namespace App\Console\Commands\Sarpras;

use App\Services\Sarpras\Automation\AssetHealthService;
use App\Services\Sarpras\Automation\CriticalityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RecomputeAssetHealthCommand extends Command
{
    protected $signature = 'sarpras:recompute-health
                            {--limit=200 : Batch size for chunked recompute}';

    protected $description = 'Recompute asset health & criticality metrics for all assets';

    protected AssetHealthService $health;
    protected CriticalityService $criticality;

    public function __construct(AssetHealthService $health, CriticalityService $criticality)
    {
        parent::__construct();
        $this->health = $health;
        $this->criticality = $criticality;
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $start = microtime(true);
        $this->info("Recomputing asset health (limit={$limit})...");
        $healthCount = $this->health->recomputeAll($limit);
        $this->info("Asset health updated: {$healthCount}");

        $this->info("Recomputing asset criticality (limit={$limit})...");
        $criticalityCount = $this->criticality->recomputeAll($limit);
        $this->info("Asset criticality updated: {$criticalityCount}");

        $elapsed = round(microtime(true) - $start, 2);
        $this->info("Done in {$elapsed}s");

        Log::info('sarpras:recompute-health', [
            'health_count' => $healthCount,
            'criticality_count' => $criticalityCount,
            'elapsed_seconds' => $elapsed,
        ]);

        return self::SUCCESS;
    }
}