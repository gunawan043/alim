<?php

namespace App\Services\Sarpras;

use App\Models\Sparepart;
use App\Models\User;

class AutomationSuggestionService
{
    /**
     * Suggest the best vendor for a sparepart based on historical rating and price.
     */
    public function suggestVendor(int $sparepartId, ?int $vendorCategoryId = null): ?array
    {
        $sparepart = Sparepart::with('primaryVendor')->find($sparepartId);
        if (! $sparepart) {
            return null;
        }

        $vendor = $sparepart->primaryVendor;
        if (! $vendor) {
            return null;
        }

        return [
            'vendor_id' => $vendor->id,
            'vendor_code' => $vendor->vendor_code,
            'vendor_name' => $vendor->name,
            'rating' => (float) $vendor->rating_avg,
            'is_primary' => true,
            'confidence' => 'medium',
        ];
    }

    /**
     * Check all low-stock spareparts.
     */
    public function detectLowStock(): array
    {
        $lowStock = Sparepart::with(['category', 'warehouse', 'primaryVendor'])
            ->whereColumn('stock', '<=', 'reorder_point')
            ->where('is_active', true)
            ->get();

        $recommendations = [];
        foreach ($lowStock as $part) {
            if ($part->reorder_quantity > 0) {
                $qty = $part->reorder_quantity;
            } else {
                $qty = $part->max_stock - $part->stock;
            }
            if ($qty <= 0) {
                $qty = $part->min_stock * 2;
            }

            $recommendations[] = [
                'sparepart_id' => $part->id,
                'part_number' => $part->part_number,
                'name' => $part->name,
                'current_stock' => (float) $part->stock,
                'reorder_qty' => $qty,
                'estimated_cost' => $qty * (float) $part->unit_price,
                'lead_time_days' => $part->lead_time_days,
                'suggested_vendor' => $this->suggestVendor($part->id),
            ];
        }

        return $recommendations;
    }

    /**
     * Find warranty claim opportunities.
     */
    public function detectWarrantyClaims(): array
    {
        $claims = \App\Models\VendorWarranty::where('status', 'active')
            ->where('end_date', '>=', now()->subDays(30))
            ->where('end_date', '<=', now()->addDays(90))
            ->with(['vendor', 'asset'])
            ->get();

        return $claims->map(function ($w) {
            return [
                'warranty_id' => $w->id,
                'asset_id' => $w->asset_id,
                'asset_code' => $w->asset?->asset_code,
                'vendor_id' => $w->vendor_id,
                'vendor_name' => $w->vendor?->name,
                'end_date' => $w->end_date,
                'days_to_expiry' => now()->diffInDays($w->end_date),
                'coverage' => $w->coverage_type,
                'priority' => $w->days_to_expiry <= 30 ? 'critical' : 'high',
            ];
        })->toArray();
    }

    /**
     * Classify sparepart movement velocity.
     */
    public function classifyVelocity(int $sparepartId): string
    {
        $sixMonthsAgo = now()->subMonths(6);
        $totalIssued = SparepartStockMovement::where('sparepart_id', $sparepartId)
            ->where('movement_type', 'issue')
            ->where('occurred_at', '>=', $sixMonthsAgo)
            ->sum('quantity');

        if ($totalIssued > 100) {
            return 'fast';
        }
        if ($totalIssued > 10) {
            return 'normal';
        }

        return 'slow';
    }

    /**
     * Auto-reserve spareparts linked to a Work Order.
     */
    public function autoReserveForWorkOrder(string $woId, User $actor): array
    {
        $wo = \App\Models\WorkOrder::with('sparePartUsages.sparePart')->find($woId);
        if (! $wo) {
            return ['success' => false, 'error' => 'Work Order not found'];
        }

        $reservations = [];
        foreach ($wo->sparePartUsages ?? [] as $usage) {
            $part = $usage->sparePart;
            if (! $part) {
                continue;
            }

            $result = app(StockManagementService::class)->reserve(
                $part,
                (float) $usage->quantity_used,
                'work_order',
                $woId,
                $actor
            );

            if ($result['success']) {
                $reservations[] = $result;
            }
        }

        return ['success' => true, 'reservations' => $reservations];
    }
}
