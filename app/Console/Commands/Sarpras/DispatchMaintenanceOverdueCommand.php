<?php

namespace App\Console\Commands\Sarpras;

use App\Events\Sarpras\MaintenanceDue;
use App\Events\Sarpras\MaintenanceOverdue;
use App\Models\Asset;
use App\Models\MaintenanceHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchMaintenanceOverdueCommand extends Command
{
    protected $signature = 'sarpras:maintenance-overdue
                            {--warn-days=3 : Issue due warning N days ahead}';

    protected $description = 'Dispatch MaintenanceDue / MaintenanceOverdue events based on schedule';

    public function handle(): int
    {
        $warnDays = (int) $this->option('warn-days');
        $today = now();
        $horizon = now()->addDays($warnDays);

        $dueSoon = MaintenanceHistory::query()
            ->whereIn('status', ['scheduled', 'pending'])
            ->whereBetween('scheduled_date', [$today, $horizon])
            ->get();

        $this->info("Dispatching MaintenanceDue for {$dueSoon->count()} assets.");
        foreach ($dueSoon as $history) {
            $asset = Asset::find($history->asset_id);
            if (! $asset) {
                continue;
            }
            event(new MaintenanceDue($asset, $history));
        }

        $overdue = MaintenanceHistory::query()
            ->whereIn('status', ['scheduled', 'pending', 'due'])
            ->where('scheduled_date', '<', $today)
            ->get();

        $this->info("Dispatching MaintenanceOverdue for {$overdue->count()} assets.");
        foreach ($overdue as $history) {
            $asset = Asset::find($history->asset_id);
            if (! $asset) {
                continue;
            }
            $overdueDays = (int) $history->scheduled_date->diffInDays($today);
            event(new MaintenanceOverdue($asset, $history, $overdueDays));
        }

        Log::info('sarpras:maintenance-overdue', [
            'due_soon' => $dueSoon->count(),
            'overdue' => $overdue->count(),
            'warn_days' => $warnDays,
        ]);

        return self::SUCCESS;
    }
}