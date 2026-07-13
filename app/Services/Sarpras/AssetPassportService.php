<?php

namespace App\Services\Sarpras;

use App\Models\Asset;
use App\Models\AssetEventLog;
use App\Models\MaintenanceHistory;
use App\Models\QrScanHistory;
use App\Models\RepairCostHistory;
use App\Models\RepairRequest;
use App\Models\WorkOrder;
use Carbon\Carbon;

/**
 * Builds the complete Asset Passport returned when a QR is scanned.
 */
class AssetPassportService
{
    public function getForAsset(Asset $asset): array
    {
        // Warm up all relations in bulk to avoid N+1
        $related = $this->fetchRelations($asset);

        return [
            'identity' => $this->buildIdentity($asset, $related),
            'condition' => $this->buildCondition($asset, $related),
            'warranty' => $this->buildWarranty($asset),
            'history' => $this->buildHistoryTimeline($asset, $related['events']),
            'costs' => $this->buildCostSummary($asset, $related),
            'documents' => $this->buildDocuments($asset),
            'actions' => $this->buildActions($asset),
            'maintenance' => $this->buildMaintenanceSchedule($asset),
            'risk' => $this->buildRiskIndicators($asset, $related),
        ];
    }

    /** Alias for controller consumption. */
    public function build(Asset $asset): array
    {
        return $this->getForAsset($asset);
    }

    /**
     * Build the "full" passport for web display — richer than the QR scan payload.
     */
    public function buildFull(Asset $asset): array
    {
        $data = $this->getForAsset($asset);
        $data['loans'] = $this->buildLoanHistory($asset);
        $data['transfers'] = $this->buildTransferHistory($asset);
        $data['audits'] = $this->buildAuditHistory($asset);
        $data['stock_opname'] = $this->buildStockOpnameHistory($asset);
        $data['costs_detail'] = $this->buildDetailedCosts($asset);
        $data['qr_scans'] = $this->buildScanHistory($asset);
        $data['photos'] = $this->buildPhotos($asset);
        return $data;
    }

    protected function buildMaintenanceSchedule(Asset $asset): array
    {
        $log = \App\Models\AssetMaintenanceLog::where('asset_id', $asset->id)
            ->orderByDesc('maintenance_date')
            ->first();

        return [
            'last_performed' => $log?->maintenance_date?->toDateString(),
            'next_due' => $log?->next_due_date?->toDateString(),
            'overdue' => $log?->next_due_date && $log->next_due_date->lt(now()),
            'frequency' => $log?->maintenance_type,
        ];
    }

    protected function buildRiskIndicators(Asset $asset, array $related): array
    {
        $maintCount = $related['maintHistories']->count();
        $repairCount = $related['repairRequests']->count();
        $totalCost = (float) ($related['repairCosts']->sum('amount') ?? 0);

        // Asset is "critical" if it has had 3+ repairs OR > 5M in repair costs
        $isCritical = $repairCount >= 3 || $totalCost >= 5_000_000;

        return [
            'is_critical' => $isCritical,
            'repair_count' => $repairCount,
            'maintenance_count' => $maintCount,
            'total_repair_cost' => $totalCost,
            'risk_level' => match (true) {
                $repairCount >= 5 || $totalCost >= 10_000_000 => 'high',
                $isCritical => 'medium',
                default => 'low',
            },
        ];
    }

    protected function buildLoanHistory(Asset $asset): array
    {
        if (!class_exists('App\\Models\\AssetLoan')) {
            return [];
        }
        return \App\Models\AssetLoan::where('asset_id', $asset->id)
            ->orderByDesc('created_at')
            ->take(20)
            ->get(['id', 'loan_number', 'borrower_name', 'start_date', 'end_date', 'status'])
            ->toArray();
    }

    protected function buildTransferHistory(Asset $asset): array
    {
        if (!class_exists('App\\Models\\AssetTransfer')) {
            return [];
        }
        return \App\Models\AssetTransfer::where('asset_id', $asset->id)
            ->orderByDesc('created_at')
            ->take(20)
            ->get()
            ->toArray();
    }

    protected function buildAuditHistory(Asset $asset): array
    {
        if (!class_exists('App\\Models\\AssetAudit')) {
            return [];
        }
        return \App\Models\AssetAudit::where('asset_id', $asset->id)
            ->orderByDesc('created_at')
            ->take(20)
            ->get()
            ->toArray();
    }

    protected function buildStockOpnameHistory(Asset $asset): array
    {
        if (!class_exists('App\\Models\\StockOpnameItem')) {
            return [];
        }
        return \App\Models\StockOpnameItem::with('session')
            ->where('asset_id', $asset->id)
            ->orderByDesc('observed_at')
            ->take(20)
            ->get()
            ->toArray();
    }

    protected function buildDetailedCosts(Asset $asset): array
    {
        return RepairCostHistory::where('asset_id', $asset->id)
            ->orderByDesc('incurred_date')
            ->get()
            ->groupBy('cost_category')
            ->map(fn ($items, $cat) => [
                'category' => $cat,
                'count' => $items->count(),
                'total' => (float) $items->sum('amount'),
                'latest' => $items->first()?->incurred_date?->toDateString(),
            ])
            ->values()
            ->toArray();
    }

    protected function buildScanHistory(Asset $asset): array
    {
        return QrScanHistory::with('scanner')
            ->where('asset_id', $asset->id)
            ->orderByDesc('scanned_at')
            ->take(50)
            ->get()
            ->toArray();
    }

    protected function buildPhotos(Asset $asset): array
    {
        if (! $asset->photo_path) return [];
        return [
            ['path' => $asset->photo_path, 'label' => 'Primary', 'is_primary' => true],
        ];
    }

    /** Find asset by code/UUID, optionally recording a scan. */
    public function findByLookup(string $lookupValue, ?int $scannedBy = null, ?string $source = null, ?string $purpose = null): ?array
    {
        $asset = Asset::with([
            'room', 'room.building', 'workUnit', 'category', 'creator',
        ])->where(function ($q) use ($lookupValue) {
            $q->where('id', $lookupValue)
                ->orWhere('asset_code', $lookupValue);
        })->first();

        if (! $asset) {
            return null;
        }

        QrScanHistory::create([
            'asset_id' => $asset->id,
            'scanned_by' => $scannedBy,
            'scan_type' => $lookupValue === $asset->id ? 'uuid' : 'code',
            'lookup_value' => $lookupValue,
            'source' => $source,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'purpose' => $purpose,
        ]);

        return $this->getForAsset($asset);
    }

    protected function fetchRelations(Asset $asset): array
    {
        return [
            'events' => AssetEventLog::where('asset_id', $asset->id)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(),
            'maintHistories' => MaintenanceHistory::where('asset_id', $asset->id)
                ->orderByDesc('performed_date')
                ->get(),
            'repairRequests' => RepairRequest::where('asset_id', $asset->id)
                ->orderByDesc('created_at')
                ->with(['repairRequests.workOrders'])
                ->get(),
            'workOrders' => WorkOrder::where('asset_id', $asset->id)
                ->orderByDesc('created_at')
                ->take(10)
                ->get(),
            'repairCosts' => RepairCostHistory::where('asset_id', $asset->id)
                ->orderByDesc('incurred_date')
                ->get(),
        ];
    }

    protected function buildIdentity(Asset $asset, array $related): array
    {
        return [
            'asset_id' => $asset->id,
            'asset_code' => $asset->asset_code,
            'asset_name' => $asset->asset_name,
            'brand' => $asset->brand,
            'model' => $asset->model,
            'serial_number' => $asset->serial_number,
            'category' => $asset->category?->name ?? '-',
            'status' => $asset->status,
            'location' => [
                'building' => $asset->room?->building?->building_name ?? null,
                'room' => $asset->room?->room_name ?? null,
                'room_code' => $asset->room?->room_code ?? null,
            ],
            'work_unit' => $asset->workUnit?->name ?? '-',
            'pic' => $asset->creator?->name ?? '-',
            'acquisition' => [
                'date' => $asset->acquisition_date?->toDateString() ?? '-',
                'year' => $asset->acquisition_year,
                'price' => $asset->acquisition_price,
                'source' => $asset->acquisition_source,
                'supplier' => $asset->supplier_name,
            ],
        ];
    }

    protected function buildCondition(Asset $asset, array $related): array
    {
        $maintenanceCount = $related['maintHistories']->count();
        $recentMaintenance = $related['maintHistories']->first();

        return [
            'current_condition' => $asset->condition,
            'last_condition_update' => $asset->last_condition_update?->toDateString() ?? null,
            'total_maintenance' => $maintenanceCount,
            'most_recent_maintenance' => $recentMaintenance
                ? [
                    'type' => $recentMaintenance->maintenance_type,
                    'date' => $recentMaintenance->performed_date->toDateString(),
                    'desc' => $recentMaintenance->work_description,
                ]
                : null,
            'movement_count' => $related['events']->where('event_type', 'asset_moved')->count(),
            'damage_reports_count' => $related['repairRequests']->count(),
        ];
    }

    protected function buildWarranty(Asset $asset): array
    {
        if (! $asset->warranty_end_date && ! $asset->warranty_start_date) {
            return ['has_warranty' => false];
        }

        $isActive = $asset->warranty_end_date && Carbon::now()->lt(Carbon::parse($asset->warranty_end_date));

        return [
            'has_warranty' => true,
            'is_active' => $isActive,
            'provider' => $asset->warranty_provider,
            'start_date' => $asset->warranty_start_date?->toDateString(),
            'end_date' => $asset->warranty_end_date?->toDateString(),
            'terms' => $asset->warranty_terms,
            'days_remaining' => $isActive ? Carbon::now()->diffInDays(Carbon::parse($asset->warranty_end_date), false) : null,
        ];
    }

    protected function buildHistoryTimeline(Asset $asset, $events): array
    {
        return $events->map(function ($event) {
            return [
                'type' => $event->event_type,
                'date' => $event->created_at->toDateTimeString(),
                'detail' => $event->event_detail,
            ];
        })->toArray();
    }

    protected function buildCostSummary(Asset $asset, array $related): array
    {
        $purchasePrice = $asset->acquisition_price ?? 0;
        $totalMaintCost = $related['maintHistories']->sum('cost');
        $totalRepairCost = $related['repairCosts']->sum('amount');
        $currentValue = $asset->current_value ?? $asset->acquisition_price ?? 0;

        return [
            'purchase_price' => $purchasePrice,
            'total_maintenance' => $totalMaintCost,
            'total_repair' => $totalRepairCost,
            'current_book_value' => $currentValue,
            'depreciation_yearly' => $asset->depreciation_per_year,
        ];
    }

    protected function buildDocuments(Asset $asset): array
    {
        $docs = [
            'purchase_doc' => $asset->purchase_document_path,
            'photo' => $asset->photo_path,
        ];

        if ($asset->warranty_documents) {
            $docs['warranty'] = $asset->warranty_documents;
        }

        return $docs;
    }

    protected function buildActions(Asset $asset): array
    {
        $actions = [
            'view_history' => route('sarpras.aset.show', $asset->id),
            'report_damage' => null, // configurable
            'request_move' => null,
        ];

        if ($asset->status === 'tersedia' && $asset->condition === 'baik') {
            $actions['available_for_loan'] = true;
        }

        return $actions;
    }
}
