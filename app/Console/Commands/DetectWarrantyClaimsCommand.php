<?php

namespace App\Console\Commands;

use App\Events\Sarpras\WarrantyClaimOpportunity;
use App\Services\Sarpras\AutomationSuggestionService;
use Illuminate\Console\Command;

class DetectWarrantyClaimsCommand extends Command
{
    protected $signature = 'sarpras:detect-warranty-claims {--days=90 : Days to expiry threshold}';

    protected $description = 'Detect warranty claim opportunities and dispatch events';

    public function handle(AutomationSuggestionService $service): int
    {
        $recs = $service->detectWarrantyClaims();
        $this->info('Detected '.count($recs).' warranty opportunities.');

        $count = 0;
        foreach ($recs as $rec) {
            $warranty = \App\Models\VendorWarranty::find($rec['warranty_id']);
            if ($warranty) {
                event(new WarrantyClaimOpportunity($warranty, $rec['priority']));
                $count++;
            }
        }

        $this->info("Dispatched {$count} warranty claim events.");

        return self::SUCCESS;
    }
}
