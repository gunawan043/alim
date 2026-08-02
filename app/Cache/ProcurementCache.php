<?php

namespace App\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * Tag-based cache facade for procurement domain.
 *
 * Uses cache tags (Redis/Memcached) so that we can selectively invalidate
 * per-entity or per-vendor caches without flushing the entire store.
 */
class ProcurementCache
{
    private const TTL_DASHBOARD = 300;

    private const TTL_REPORT = 900;

    private const TTL_LISTING = 60;

    public function dashboard(string $organizationId, string $role, callable $producer): array
    {
        $key = "dashboard:{$organizationId}:{$role}";

        return Cache::tags(['procurement', "org:{$organizationId}"])->remember(
            $key,
            self::TTL_DASHBOARD,
            fn () => $producer(),
        );
    }

    public function rfqList(int $organizationId, callable $producer): array
    {
        $key = "rfqs:list:{$organizationId}";

        return Cache::tags(['rfqs', "org:{$organizationId}"])->remember(
            $key,
            self::TTL_LISTING,
            fn () => $producer(),
        );
    }

    public function quotationList(int $organizationId, array $filters, callable $producer): array
    {
        ksort($filters);
        $filterHash = md5(json_encode($filters));
        $key = "quotations:list:{$organizationId}:{$filterHash}";

        return Cache::tags(['quotations', "org:{$organizationId}"])->remember(
            $key,
            self::TTL_LISTING,
            fn () => $producer(),
        );
    }

    public function purchaseOrderList(int $organizationId, array $filters, callable $producer): array
    {
        ksort($filters);
        $filterHash = md5(json_encode($filters));
        $key = "pos:list:{$organizationId}:{$filterHash}";

        return Cache::tags(['purchase_orders', "org:{$organizationId}"])->remember(
            $key,
            self::TTL_LISTING,
            fn () => $producer(),
        );
    }

    public function vendorProfile(int $vendorId, callable $producer): mixed
    {
        $key = "vendor:profile:{$vendorId}";

        return Cache::tags(['vendors', "vendor:{$vendorId}"])->remember(
            $key,
            self::TTL_REPORT,
            fn () => $producer(),
        );
    }

    public function vendorPerformanceReport(int $vendorId, string $period, callable $producer): array
    {
        $key = "vendor:perf:{$vendorId}:{$period}";

        return Cache::tags(['vendors', "vendor:{$vendorId}", 'vendor_reports'])->remember(
            $key,
            self::TTL_REPORT,
            fn () => $producer(),
        );
    }

    public function invalidateRfq(int $organizationId, ?int $vendorId = null): void
    {
        $tags = ['rfqs', "org:{$organizationId}"];
        if ($vendorId !== null) {
            $tags[] = "vendor:{$vendorId}";
        }
        Cache::tags($tags)->flush();
    }

    public function invalidateQuotation(int $organizationId, int $vendorId, int $rfqId): void
    {
        Cache::tags([
            'quotations',
            "org:{$organizationId}",
            "vendor:{$vendorId}",
            "rfq:{$rfqId}",
        ])->flush();
    }

    public function invalidatePurchaseOrder(int $organizationId, int $vendorId): void
    {
        Cache::tags([
            'purchase_orders',
            'purchase_order_dashboard',
            "org:{$organizationId}",
            "vendor:{$vendorId}",
        ])->flush();
    }

    public function invalidateVendor(int $vendorId): void
    {
        Cache::tags(['vendors', "vendor:{$vendorId}", 'vendor_reports'])->flush();
    }

    public function invalidateDashboard(int $organizationId, ?string $role = null): void
    {
        $tags = ['procurement', "org:{$organizationId}"];
        if ($role !== null) {
            $tags[] = "role:{$role}";
        }
        Cache::tags($tags)->flush();
    }
}
