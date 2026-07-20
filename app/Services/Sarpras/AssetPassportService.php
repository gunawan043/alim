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
 * Covers identity, photo, specification, room, building, PIC, purchase,
 * vendor, warranty, depreciation, repair/maintenance/movement history,
 * stock opname, sparepart used, repair cost, health score, criticality,
 * lifecycle timeline, loan history, audit history, current/ book value.
 */
class AssetPassportService
{
    public function getForAsset(Asset $asset): array
    {
        $related = $this->fetchRelations($asset);

        return [
            'identity' => $this->buildIdentity($asset, $related),
            'health' => $this->buildHealthScore($asset, $related),
            'criticality' => $this->buildCriticality($asset, $related),
            'warranty' => $this->buildWarranty($asset),
            'financial' => $this->buildFinancials($asset, $related),
            'history' => $this->buildHistoryTimeline($asset, $related['events']),
            'costs' => $this->buildCostSummary($asset, $related),
            'documents' => $this->buildDocuments($asset),
            'lifecycle' => $this->buildLifecycleTimeline($asset, $related['events']),
        ];
    }

    /** Alias for controller consumption. */
    public function build(Asset $asset): array
    {
        return $this->getForAsset($asset);
    }

    /**
     * Full passport — enriched with loans, transfers, audits, opname, photos.
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
        $data['spareparts_used'] = $this->buildSparepartHistory($asset);
        $data['technician_performance'] = $this->buildTechnicianNotes($asset, $related = $this->fetchRelations($asset));
        return $data;
    }

    /**
     * Mobile scan payload — lightweight, fast.
     */
    public function getForMobileScan(Asset $asset): array
    {
        $related = $this->fetchRelations($asset);

        return [
            'name' => $asset->asset_name,
            'asset_code' => $asset->asset_code,
            'status' => $asset->status,
            'condition' => $asset->condition,
            'room' => $asset->room?->room_name ?? '−',
            'room_code' => $asset->room?->room_code,
            'building' => $asset->room?->building?->building_name ?? '−',
            'pic' => $asset->creator?->name ?? '−',
            'health' => $this->buildHealthScore($asset, $related),
            'warranty' => $this->buildWarranty($asset),
            'maintenance_due' => $this->buildMaintenanceSchedule($asset),
            'recent_repairs' => RepairRequest::where('asset_id', $asset->id)
                ->orderByDesc('created_at')
                ->take(5)
                ->get(['id', 'request_number', 'status', 'created_at'])
                ->toArray(),
        ];
    }

    /**
     * Quick health summary — used on dashboards / list views.
     */
    public function getHealthSummary(Asset $asset): array
    {
        return $this->buildHealthScore($asset, $this->fetchRelations($asset));
    }

    /**
     * Lifecycle — ordered events from creation to now.
     */
    public function getLifecycle(Asset $asset): array
    {
        return $this->buildLifecycleTimeline($asset, $this->fetchRelations($asset)['events']);
    }

    /**
     * Cost analytics — repair cost per asset, aggregated.
     */
    public function getCostAnalytics(int $schoolId): array
    {
        $assets = Asset::where('school_id', $schoolId)
            ->with(['category', 'room', 'room.building', 'workUnit'])
            ->get();

        $analytics = [];
        foreach ($assets as $a) {
            $costData = RepairCostHistory::where('asset_id', $a->id)->sum('amount');
            if ($costData > 0) {
                $analytics[] = [
                    'asset' => $a->asset_name,
                    'code' => $a->asset_code,
                    'category' => $a->category?->name,
                    'room' => $a->room?->room_name,
                    'building' => $a->room?->building?->building_name,
                    'total_repair_cost' => (float) $costData,
                    'work_orders' => WorkOrder::where('asset_id', $a->id)->count(),
                ];
            }
        }

        usort($analytics, fn ($a, $b) => $b['total_repair_cost'] <=> $a['total_repair_cost']);

        return $analytics;
    }

    /**
     * Predictive maintenance — MTBF, MTTR, health trend, recommendations.
     */
    public function getPredictiveMaintenance(int $schoolId): array
    {
        $assets = Asset::where('school_id', $schoolId)
            ->with(['category', 'room'])
            ->withExists([
                'repairRequests as repair_count' => function ($q) {
                    $q->where('status', 'resolved');
                },
            ])
            ->get();

        $predictions = [];
        foreach ($assets as $a) {
            $repairs = RepairRequest::where('asset_id', $a->id)
                ->orderBy('created_at')
                ->get();

            if ($repairs->count() < 2) {
                continue;
            }

            $intervals = [];
            $timespan = $repairs->last()->created_at->diffInDays($repairs->first()->created_at);
            foreach ($repairs->slice(1) as $r) {
                $prev = $repairs->first(function ($prev) use ($r) {
                    return $prev->created_at?->lt($r->created_at);
                });
                if ($prev) {
                    $intervals[] = $r->created_at->diffInDays($prev->created_at);
                }
            }

            $mtbf = ! empty($intervals)
                ? array_sum($intervals) / count($intervals)
                : 0;
            $avgRepairsPerMonth = ($timespan > 0)
                ? ($repairs->count() / ($timespan / 30))
                : 0;

            $recommendation = null;
            if ($avgRepairsPerMonth > 0.3 || $repairs->count() >= 5) {
                $recommendation = sprintf(
                    'Asset %s mengalami perbaikan %d kali (%.1f per bulan). Pertimbangkan penggantian.',
                    $a->asset_name,
                    $repairs->count(),
                    $avgRepairsPerMonth
                );
            }

            $predictions[] = [
                'asset' => $a->asset_name,
                'code' => $a->asset_code,
                'category' => $a->category?->name,
                'room' => $a->room?->room_name,
                'repair_count' => $repairs->count(),
                'mtbf_days' => round($mtbf),
                'repairs_per_month' => round($avgRepairsPerMonth, 2),
                'health_trend' => $avgRepairsPerMonth > 0.3 ? 'declining' : 'stable',
                'recommendation' => $recommendation,
            ];
        }

        usort($predictions, fn ($a, $b) => ($b['repair_count'] ?? 0) <=> ($a['repair_count'] ?? 0));

        return $predictions;
    }

    /**
     * Warranty engine — check warranty before creating WO.
     */
    public function checkWarranty(Asset $asset): array
    {
        if (! $asset->warranty_end_date) {
            return [
                'has_warranty' => false,
                'vendor' => null,
                'service_center' => null,
                'action' => 'create_internal_wo',
            ];
        }

        $isActive = Carbon::now()->lt(Carbon::parse($asset->warranty_end_date));

        if (! $isActive) {
            return [
                'has_warranty' => true,
                'expired' => true,
                'action' => 'create_internal_wo',
            ];
        }

        // Active warranty — suggest vendor service center path
        $vendor = null;
        if ($asset->supplier_id) {
            $vendor = \App\Models\SchoolVendor::where('id', $asset->supplier_id)->first();
        }

        return [
            'has_warranty' => true,
            'expired' => false,
            'provider' => $asset->warranty_provider,
            'end_date' => $asset->warranty_end_date?->toDateString(),
            'days_remaining' => Carbon::now()->diffInDays(Carbon::parse($asset->warranty_end_date)),
            'vendor' => $vendor ? $vendor->vendor_name : null,
            'action' => 'route_to_vendor_service',
        ];
    }

    /**
     * Technician performance — leaderboard data.
     */
    public function getTechnicianPerformance(?int $schoolId = null): array
    {
        $woQuery = WorkOrder::with(['assignedTo', 'repairRequest.asset']);
        if ($schoolId) {
            $woQuery->whereHas('repairRequest', fn ($q) => $q->where('school_id', $schoolId));
        }
        $workOrders = $woQuery->get();

        $stats = [];
        foreach ($workOrders as $wo) {
            $tech = $wo->assignedTo;
            if (! $tech) {
                continue;
            }
            $key = $tech->id;
            if (! isset($stats[$key])) {
                $stats[$key] = [
                    'id' => $tech->id,
                    'name' => $tech->name,
                    'jobs_completed' => 0,
                    'total_delay_minutes' => 0,
                    'sla_success' => 0,
                    'sla_failed' => 0,
                    'total_repair_cost' => 0,
                ];
            }

            if ($wo->status === 'completed') {
                $stats[$key]['jobs_completed']++;
                if ($wo->priority === 'normal') {
                    $stats[$key]['sla_success']++;
                } else {
                    $stats[$key]['sla_failed']++;
                }
            }

            $repairCost = RepairCostHistory::where('work_order_id', $wo->id)->sum('amount');
            $stats[$key]['total_repair_cost'] += (float) $repairCost;
        }

        $leaderboard = array_values($stats);
        usort($leaderboard, fn ($a, $b) => $b['jobs_completed'] <=> $a['jobs_completed']);

        return $leaderboard;
    }

    /**
     * Vendor performance — delivery, repair, cost, warranty metrics.
     */
    public function getVendorPerformance(): array
    {
        $vendors = \App\Models\SchoolVendor::all();

        $results = [];
        foreach ($vendors as $vendor) {
            $procurements = \App\Models\ProcurementRequest::where('vendor_id', $vendor->id)->get();

            $totalDelivery = $procurements->count();
            $lateDelivery = $procurements->where('approval_status', 'delivered')
                ->filter(fn ($pr) => $pr->received_date?->gt($pr->expected_delivery_date))->count();

            $rejectedItems = \App\Models\ProcurementRequestItem::whereHas(
                'procurementRequest', fn ($q) => $q->where('vendor_id', $vendor->id)
            )->where('quantity_received', '<', 'quantity_ordered')
                ->get()
                ->count();

            $repairOrders = WorkOrder::whereHas('repairRequest', function ($q) use ($vendor) {
                $q->where('asset_id', function ($q2) use ($vendor) {
                    $q2->where('supplier_id', $vendor->id);
                });
            })->count();

            $results[] = [
                'id' => $vendor->id,
                'name' => $vendor->vendor_name,
                'type' => $vendor->vendor_type,
                'deliveries_completed' => $totalDelivery,
                'late_deliveries' => $lateDelivery,
                'rejected_items' => $rejectedItems,
                'repair_orders' => $repairOrders,
            ];
        }

        return $results;
    }

    // ── Health score (0-100) ──────────────────────────────────────────

    protected function buildHealthScore(Asset $asset, array $related): array
    {
        $repairs = $related['repairRequests'];
        $costs = $related['repairCosts'];
        $maints = $related['maintHistories'];

        // Start at 100, deduct for negatives
        $score = 100;

        // Deduct for each repair
        $score -= $repairs->count() * 5;

        // Deduct for high repair costs
        $totalRepairCost = (float) $costs->sum('amount');
        if ($totalRepairCost > 10_000_000) $score -= 20;
        elseif ($totalRepairCost > 5_000_000) $score -= 15;
        elseif ($totalRepairCost > 1_000_000) $score -= 10;

        // Bonus for regular maintenance
        if ($maints->count() > 0) $score += 5;

        // Condition penalty
        $conditionPenalty = match ($asset->condition) {
            'rusak_ringan' => 5,
            'rusak_sedang' => 15,
            'rusak_berat' => 30,
            'hilang' => 100,
            default => 0,
        };
        $score -= $conditionPenalty;

        $score = max(0, min(100, $score));

        $level = match (true) {
            $score >= 80 => 'excellent',
            $score >= 60 => 'good',
            $score >= 40 => 'fair',
            $score >= 20 => 'poor',
            default => 'critical',
        };

        return [
            'score' => $score,
            'level' => $level,
            'last_checked' => now()->toDateString(),
            'factors' => [
                'repair_count' => $repairs->count(),
                'total_repair_cost' => $totalRepairCost,
                'maintenance_compliance' => $maints->count(),
                'condition' => $asset->condition,
            ],
        ];
    }

    protected function buildCriticality(Asset $asset, array $related): array
    {
        $repairCount = $related['repairRequests']->count();
        $totalCost = (float) $related['repairCosts']->sum('amount');
        $isCritical = $repairCount >= 3 || $totalCost >= 5_000_000;

        return [
            'is_critical' => $isCritical,
            'repair_count' => $repairCount,
            'maintenance_count' => $related['maintHistories']->count(),
            'total_repair_cost' => $totalCost,
            'risk_level' => match (true) {
                $repairCount >= 5 || $totalCost >= 10_000_000 => 'high',
                $isCritical => 'medium',
                default => 'low',
            },
        ];
    }

    protected function buildFinancials(Asset $asset, array $related): array
    {
        $purchasePrice = $asset->acquisition_price ?? 0;
        $totalMaintCost = $related['maintHistories']->sum('cost');
        $totalRepairCost = $related['repairCosts']->sum('amount');
        $currentValue = $asset->current_value ?? $asset->acquisition_price ?? 0;

        return [
            'purchase_price' => $purchasePrice,
            'total_maintenance' => (float) $totalMaintCost,
            'total_repair' => $totalRepairCost,
            'current_book_value' => $currentValue,
            'depreciation_yearly' => $asset->depreciation_per_year,
            'total_investment' => $purchasePrice + $totalMaintCost + $totalRepairCost,
        ];
    }

    protected function buildLifecycleTimeline(Asset $asset, $events): array
    {
        // Events already sorted desc by created_at
        return $events->map(function ($event) {
            return [
                'date' => $event->created_at->toDateTimeString(),
                'event' => $event->event_type,
                'detail' => $event->event_detail,
                'changed_by' => $event->actor_id,
            ];
        })->values()->toArray();
    }

    protected function buildSparepartHistory(Asset $asset): array
    {
        return RepairCostHistory::where('asset_id', $asset->id)
            ->whereNotNull('sparepart_used')
            ->orWhere('cost_category', 'sparepart')
            ->orderByDesc('incurred_date')
            ->take(20)
            ->get(['id', 'work_order_id', 'cost_category', 'sparepart_used', 'amount', 'incurred_date'])
            ->toArray();
    }

    protected function buildTechnicianNotes(array $related): array
    {
        return $related['workOrders']->map(function ($wo) {
            return [
                'order_number' => $wo->order_number,
                'status' => $wo->status,
                'assigned_to' => $wo->assignedTo?->name ?? 'Unassigned',
                'created_at' => $wo->created_at->toDateTimeString(),
            ];
        })->toArray();
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
