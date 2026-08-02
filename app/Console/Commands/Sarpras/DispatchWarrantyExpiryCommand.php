<?php

namespace App\Console\Commands\Sarpras;

use App\Events\Sarpras\WarrantyExpired;
use App\Models\Asset;
use App\Services\Sarpras\Automation\SarprasNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchWarrantyExpiryCommand extends Command
{
    protected $signature = 'sarpras:warranty-warnings
                            {--days=30 : Days threshold for upcoming-expiry notifications}';

    protected $description = 'Dispatch warranty expiry events for assets whose warranty ends within N days';

    protected SarprasNotificationService $notifier;

    public function __construct(SarprasNotificationService $notifier)
    {
        parent::__construct();
        $this->notifier = $notifier;
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $today = now();
        $horizon = now()->addDays($days);

        $assets = Asset::query()
            ->whereNotNull('garansi_berakhir')
            ->whereBetween('garansi_berakhir', [$today, $horizon])
            ->get();

        $this->info("Found {$assets->count()} assets with warranty ending in <= {$days} days.");

        foreach ($assets as $asset) {
            $daysUntilExpiry = (int) $today->diffInDays($asset->garansi_berakhir, false);
            event(new WarrantyExpired($asset, $daysUntilExpiry));
        }

        $expired = Asset::query()
            ->whereNotNull('garansi_berakhir')
            ->where('garansi_berakhir', '<', $today)
            ->get();

        $this->info("Also dispatching for {$expired->count()} already-expired assets.");

        foreach ($expired as $asset) {
            $daysUntilExpiry = (int) $today->diffInDays($asset->garansi_berakhir, false);
            event(new WarrantyExpired($asset, $daysUntilExpiry));
        }

        Log::info('sarpras:warranty-warnings', [
            'upcoming' => $assets->count(),
            'expired' => $expired->count(),
            'horizon_days' => $days,
        ]);

        return self::SUCCESS;
    }
}
