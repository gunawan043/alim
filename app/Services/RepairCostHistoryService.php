<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\RepairCostHistory;
use App\Models\RepairRequest;
use Illuminate\Support\Facades\DB;

class RepairCostHistoryService
{
    public function __construct() {}

    public function record(RepairRequest $rr, float $amount, string $description, array $data = []): RepairCostHistory
    {
        return DB::transaction(function () use ($rr, $amount, $description, $data) {
            $entry = RepairCostHistory::create([
                'asset_id' => $rr->asset_id,
                'repair_request_id' => $rr->id,
                'cost_category' => $data['cost_category'] ?? 'labor',
                'description' => $description,
                'amount' => $amount,
                'incurred_date' => $data['incurred_date'] ?? now()->toDateString(),
                'document_number' => $data['document_number'] ?? null,
                'vendor_name' => $data['vendor_name'] ?? null,
                'recorded_by' => auth()->id() ?? null,
            ]);

            $this->audit($rr, 'cost_recorded', compact('amount'));

            return $entry;
        });
    }

    public function getCosts(RepairRequest $rr): array
    {
        return $rr->costHistories->all();
    }

    public function getTotalCost(RepairRequest $rr): float
    {
        return $rr->costHistories->sum('amount') + (float) ($rr->labor_cost ?? 0);
    }

    public function getCostSummary(int $assetId): array
    {
        $histories = RepairCostHistory::where('asset_id', $assetId)->get();

        $byCategory = $histories->groupBy('cost_category')
            ->map(fn ($items) => $items->sum('amount'))
            ->toArray();

        return [
            'total_cost' => (float) $histories->sum('amount'),
            'total_labor_cost' => (float) RepairRequest::where('asset_id', $assetId)->sum('labor_cost'),
            'by_category' => $byCategory,
            'count' => $histories->count(),
        ];
    }

    private function audit(RepairRequest $rr, string $action, array $meta = []): void
    {
        AuditTrail::create([
            'actor_id' => auth()->id(),
            'action' => "repair_cost.{$action}",
            'entity_type' => RepairRequest::class,
            'entity_id' => $rr->id,
            'metadata' => array_merge([
                'request_number' => $rr->request_number,
            ], $meta),
        ]);
    }
}
