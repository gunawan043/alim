<?php

namespace App\Console\Commands;

use App\Events\Sarpras\LowStockDetected;
use App\Services\Sarpras\AutomationSuggestionService;
use Illuminate\Console\Command;

class DetectLowStockCommand extends Command
{
    protected $signature = 'sarpras:detect-low-stock {--limit=100 : Max records to process}';
    protected $description = 'Detect low stock and dispatch reorder recommendations';

    public function handle(AutomationSuggestionService $service): int
    {
        $recs = $service->detectLowStock();
        $this->info("Detected {$this->option('limit')} low stock items. Total: " . count($recs));

        $count = 0;
        foreach ($recs as $rec) {
            if ($count >= $this->option('limit')) break;
            event(new LowStockDetected(\App\Models\Sparepart::find($rec['sparepart_id']), $rec['reorder_qty']));
            $count++;
        }

        $this->info("Dispatched {$count} reorder recommendation events.");
        return self::SUCCESS;
    }
}