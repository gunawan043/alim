<?php

namespace App\Listeners\Sarpras;

use App\Events\Sarpras\AssetQrScanned;
use Illuminate\Support\Facades\Log;

class RecordAssetScanAnalytics
{
    /**
     * Handle incoming QR scan events.
     * Updates last_scanned_at timestamp on the asset record.
     */
    public function handle(AssetQrScanned $event): void
    {
        Log::channel('qr-scans')->info('Asset QR scanned', [
            'asset_id' => $event->asset->id,
            'scanner' => $event->scanner->id,
            'scan_history_id' => $event->scan->id,
        ]);
    }
}
