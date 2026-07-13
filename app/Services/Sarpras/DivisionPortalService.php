<?php

namespace App\Services\Sarpras;

use App\Models\Asset;
use App\Models\DivisionBudget;
use App\Models\DivisionInventory;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use App\Services\SarprasCacheInvalidator;

class DivisionPortalService
{
    public function __construct(
        protected AssetEventLogger $eventLogger,
        protected SarprasCacheInvalidator $cacheInvalidator,
    ) {}

    public function portfolio(User $user, string $divisionId): array
    {
        $assets = Asset::where('work_unit_id', $divisionId)
            ->with('category', 'room', 'healthMetric')
            ->get();

        $byCondition = $assets->groupBy(fn ($a) => $a->condition ?? 'unknown')
            ->map(fn ($g) => $g->count())
            ->all();

        $byStatus = $assets->groupBy('asset_status')
            ->map(fn ($g) => $g->count())
            ->all();

        $totalValue = $assets->sum('acquisition_value');

        $openOrders = WorkOrder::whereHas('asset', fn ($q) => $q->where('work_unit_id', $divisionId))
            ->whereIn('status', ['assigned', 'in_progress', 'paused'])
            ->count();

        $inventory = DivisionInventory::where('division_id', $divisionId)
            ->with('asset.category')
            ->get();

        return [
            'division_id' => $divisionId,
            'summary' => [
                'total_assets' => $assets->count(),
                'total_value' => $totalValue,
                'open_orders' => $openOrders,
            ],
            'by_condition' => $byCondition,
            'by_status' => $byStatus,
            'inventories' => $inventory->map(fn ($i) => [
                'id' => $i->id,
                'asset_id' => $i->asset_id,
                'asset_name' => $i->asset?->asset_name,
                'asset_code' => $i->asset?->asset_code,
                'category' => $i->asset?->category?->name,
                'responsible_user' => $i->responsible_user_id,
                'custody_since' => $i->custody_since?->toDateString(),
            ])->all(),
        ];
    }

    public function budgets(string $divisionId): array
    {
        $budgets = DivisionBudget::where('division_id', $divisionId)
            ->orderByDesc('fiscal_year')
            ->get();

        $current = $budgets->first(fn ($b) => $b->fiscal_year == now()->year);

        return [
            'current' => $current?->only(['fiscal_year', 'allocated_amount', 'used_amount', 'reserved_amount']) ?: [],
            'remaining' => $current?->remaining_amount ?? 0,
            'all' => $budgets->map(fn ($b) => [
                'fiscal_year' => $b->fiscal_year,
                'allocated' => $b->allocated_amount,
                'used' => $b->used_amount,
                'reserved' => $b->reserved_amount,
                'remaining' => $b->remaining_amount,
                'utilization_pct' => $b->utilizationPercentage(),
            ]),
        ];
    }

    public function requestAsset(User $user, string $divisionId, array $payload): array
    {
        return DB::transaction(function () use ($user, $divisionId, $payload) {
            // 1. Verify budget has room
            $budget = DivisionBudget::where('division_id', $divisionId)
                ->where('fiscal_year', now()->year)
                ->first();

            if (! $budget) {
                throw new \DomainException("No budget allocated for fiscal year ".now()->year);
            }

            $estimatedCost = (float) ($payload['estimated_cost'] ?? 0);
            if ($budget->remaining_amount < $estimatedCost) {
                throw new \DomainException("Insufficient budget. Remaining: ".number_format($budget->remaining_amount, 0));
            }

            // 2. Reserve budget for the request.
            $budget->increment('reserved_amount', $estimatedCost);

            // 3. Generate WorkOrder using approved budget reservation.
            // Implementation deliberately deferred to the procurement / asset registration flow —
            // this method just persists the reservation and returns a ticket.
            $ticketNumber = sprintf('REQ-%d-%05d', now()->year, random_int(1, 99999));

            $this->cacheInvalidator->invalidate('portfolio.'.$divisionId);

            return [
                'ticket_number' => $ticketNumber,
                'budget_reserved' => $estimatedCost,
                'budget_remaining_after' => $budget->remaining_amount - $estimatedCost,
                'division_id' => $divisionId,
                'requested_by' => $user->id,
                'requested_at' => now()->toDateTimeString(),
            ];
        });
    }

    public function reserveBudget(string $divisionId, float $amount, string $purpose): DivisionBudget
    {
        $budget = DivisionBudget::where('division_id', $divisionId)
            ->where('fiscal_year', now()->year)
            ->firstOrFail();

        if ($budget->remaining_amount < $amount) {
            throw new \DomainException(
                "Insufficient remaining budget. Available: ".number_format($budget->remaining_amount, 0)
            );
        }

        $budget->increment('reserved_amount', $amount);
        $budget->update(['last_purpose' => $purpose]);

        return $budget->fresh();
    }

    public function settleExpense(string $divisionId, float $actualCost): DivisionBudget
    {
        $budget = DivisionBudget::where('division_id', $divisionId)
            ->where('fiscal_year', now()->year)
            ->firstOrFail();

        $budget->update([
            'reserved_amount' => max(0, $budget->reserved_amount - $actualCost),
            'used_amount' => $budget->used_amount + $actualCost,
        ]);

        $this->cacheInvalidator->invalidate('portfolio.'.$divisionId);

        return $budget->fresh();
    }
}