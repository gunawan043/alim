<?php

namespace App\Console\Commands\Sarpras;

use App\Events\Sarpras\AssetMoved;
use App\Events\Sarpras\LoanOverdue;
use App\Models\AssetLoan;
use App\Models\AssetMovement;
use Illuminate\Console\Command;

class DispatchAssetMovedEvent extends Command
{
    protected $signature = 'sarpras:movement-events';

    protected $description = 'Dispatch AssetMoved and LoanOverdue events';

    public function handle(): int
    {
        $since = now()->subMinutes(5);

        $movements = AssetMovement::where('created_at', '>=', $since)
            ->whereNull('moved_event_dispatched')
            ->with(['asset', 'fromRoom', 'toRoom'])
            ->get();

        foreach ($movements as $movement) {
            if ($movement->fromRoom_id !== $movement->toRoom_id) {
                AssetMoved::dispatch($movement->asset, $movement->fromRoom, $movement->toRoom, $movement);
            }
            $movement->update(['moved_event_dispatched' => true]);
        }

        $overdueLoans = AssetLoan::with(['asset', 'borrower'])
            ->whereNull('actual_return_date')
            ->whereDate('expected_return_date', '<', now())
            ->whereNull('overdue_event_dispatched_at')
            ->get();

        foreach ($overdueLoans as $loan) {
            LoanOverdue::dispatch($loan->asset, $loan, $loan->borrower);
            $loan->update(['overdue_event_dispatched_at' => now()]);
        }

        $this->info("Dispatched: {$movements->count()} AssetMoved, {$overdueLoans->count()} LoanOverdue.");

        return 0;
    }
}
