<?php

namespace App\Console\Commands\Sarpras;

use App\Models\Asset;
use App\Services\Sarpras\RepairVsReplaceService;
use Illuminate\Console\Command;

class EvaluateRepairVsReplace extends Command
{
    protected $signature = 'sarpras:repair-vs-replace
        {--asset= : Evaluate a single asset ID}
        {--school= : Limit to one school ID}
        {--all : Evaluate every active asset across the system}';

    protected $description = 'Evaluate repair-vs-replace recommendations for assets';

    public function handle(RepairVsReplaceService $service): int
    {
        $query = Asset::query()->where('is_active', true);

        if ($assetId = $this->option('asset')) {
            $query->where('id', $assetId);
        } elseif ($schoolId = $this->option('school')) {
            $query->where('school_id', $schoolId);
        } elseif (! $this->option('all')) {
            $this->error('Pass --asset=<id>, --school=<id>, or --all.');

            return self::FAILURE;
        }

        $count = 0;
        $query->chunkById(100, function ($assets) use ($service, &$count) {
            foreach ($assets as $asset) {
                $rec = $service->persist($asset);
                $this->line(sprintf(
                    '[%s] %s — score=%d rationale=%s',
                    $rec->recommendation,
                    $asset->asset_code,
                    $rec->health_score,
                    collect($rec->rationale)->first() ?? ''
                ));
                $count++;
            }
        });

        $this->info("Evaluated {$count} asset(s).");

        return self::SUCCESS;
    }
}
