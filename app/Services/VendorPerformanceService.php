<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Vendor;
use App\Models\VendorCommunication;
use App\Models\VendorDocument;
use App\Models\VendorPerformanceHistory;
use App\Models\VendorRating;
use Illuminate\Support\Facades\DB;

class VendorPerformanceService
{
    public function __construct() {}

    public function recordSnapshot(Vendor $vendor, array $metrics = []): VendorPerformanceHistory
    {
        return DB::transaction(function () use ($vendor, $metrics) {
            $snapshot = VendorPerformanceHistory::create([
                'vendor_id' => $vendor->id,
                'snapshot_date' => now()->toDateString(),
                'rating_avg' => $metrics['rating_avg'] ?? $this->currentRatingAverage($vendor),
                'rating_count' => $metrics['rating_count'] ?? $this->ratingCount($vendor),
                'active_orders' => $metrics['active_orders'] ?? $this->activeOrders($vendor),
                'on_time_pct' => $metrics['on_time_pct'] ?? $this->onTimePercentage($vendor),
                'total_value_ytd' => $metrics['total_value_ytd'] ?? $this->totalValueYTD($vendor),
            ]);

            $this->audit($vendor, 'performance_snapshot', ['date' => $snapshot->snapshot_date]);

            return $snapshot;
        });
    }

    public function calculateRatingAverage(Vendor $vendor): float
    {
        return $this->currentRatingAverage($vendor);
    }

    public function getAllMetrics(Vendor $vendor): array
    {
        return [
            'rating_average' => $this->currentRatingAverage($vendor),
            'total_ratings' => $this->ratingCount($vendor),
            'active_orders' => $this->activeOrders($vendor),
            'on_time_delivery_pct' => $this->onTimePercentage($vendor),
            'total_value_ytd' => $this->totalValueYTD($vendor),
            'quality_score' => $this->qualityScore($vendor),
            'compliance_score' => $this->complianceScore($vendor),
            'response_time' => $this->responseTime($vendor),
            'complaint_count' => $this->complaintCount($vendor),
        ];
    }

    public function generateScorecard(Vendor $vendor): array
    {
        $metrics = $this->getAllMetrics($vendor);

        $scores = [
            'quality' => $metrics['quality_score'],
            'delivery' => $metrics['on_time_delivery_pct'],
            'responsiveness' => max(0, 100 - $metrics['response_time']),
            'compliance' => $metrics['compliance_score'],
            'financial' => min(100, ($metrics['total_value_ytd'] / 1000000000) * 100),
            'rating' => $metrics['rating_average'] * 20, // Scale 5 to 100
        ];

        $weightedScores = [
            'quality' => $scores['quality'] * 0.25,
            'delivery' => $scores['delivery'] * 0.25,
            'responsiveness' => $scores['responsiveness'] * 0.2,
            'compliance' => $scores['compliance'] * 0.15,
            'financial' => $scores['financial'] * 0.05,
            'rating' => $scores['rating'] * 0.1,
        ];

        $totalScore = array_sum($weightedScores);

        $grade = ($totalScore >= 90 ? 'A+' :
                 ($totalScore >= 80 ? 'A' :
                 ($totalScore >= 70 ? 'B' :
                 ($totalScore >= 60 ? 'C' : 'D'))));

        return [
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->name,
            'score' => round($totalScore, 2),
            'grade' => $grade,
            'breakdown' => $weightedScores,
            'metrics' => $metrics,
            'calculated_at' => now()->toISOString(),
        ];
    }

    private function currentRatingAverage(Vendor $vendor): float
    {
        $avg = VendorRating::where('vendor_id', $vendor->id)->avg('rating');
        return (float) ($avg ?? 0);
    }

    private function ratingCount(Vendor $vendor): int
    {
        return (int) VendorRating::where('vendor_id', $vendor->id)->count();
    }

    private function activeOrders(Vendor $vendor): int
    {
        return (int) \App\Models\PurchaseOrder::where('vendor_id', $vendor->id)
            ->whereIn('status', ['draft', 'approved', 'ordered', 'partial_delivered'])
            ->count();
    }

    private function onTimePercentage(Vendor $vendor): float
    {
        $orders = \App\Models\PurchaseOrder::where('vendor_id', $vendor->id)
            ->whereNotNull('expected_date')
            ->get();

        if ($orders->isEmpty()) {
            return 0.0;
        }

        $onTime = $orders->filter(function ($po) {
            return $po->expected_date && now()->gte($po->expected_date);
        })->count();

        return round(($onTime / $orders->count()) * 100, 2);
    }

    private function totalValueYTD(Vendor $vendor): float
    {
        return \App\Models\PurchaseOrder::where('vendor_id', $vendor->id)
            ->whereYear('expected_date', now()->year)
            ->where('status', '!=', 'draft')
            ->sum('total_amount');
    }

    private function qualityScore(Vendor $vendor): float
    {
        $ratings = VendorRating::where('vendor_id', $vendor->id)
            ->whereNotNull('quality_score')
            ->pluck('quality_score');

        return $ratings->isEmpty() ? 50.0 : round($ratings->avg() * 20, 2);
    }

    private function complianceScore(Vendor $vendor): float
    {
        $validDocs = VendorDocument::where('vendor_id', $vendor->id)
            ->where('is_valid', true)
            ->count();

        $totalDocs = VendorDocument::where('vendor_id', $vendor->id)->count();

        return $totalDocs > 0 ? round(($validDocs / $totalDocs) * 100, 2) : 100.0;
    }

    private function responseTime(Vendor $vendor): int
    {
        // Count hours since last vendor communication response
        $lastComm = VendorCommunication::where('vendor_id', $vendor->id)
            ->where('direction', 'inbound')
            ->orderByDesc('created_at')
            ->first();

        if (!$lastComm || !$lastComm->created_at) {
            return 999; // No response
        }

        return (int) round(now()->diffInHours($lastComm->created_at));
    }

    private function complaintCount(Vendor $vendor): int
    {
        return (int) VendorRating::where('vendor_id', $vendor->id)
            ->where('rating', '<=', 2)
            ->count();
    }

    private function audit(Vendor $vendor, string $action, array $meta = []): void
    {
        AuditTrail::create([
            'actor_id' => auth()->id(),
            'action' => "vendor_perf.{$action}",
            'entity_type' => Vendor::class,
            'entity_id' => $vendor->id,
            'metadata' => array_merge([
                'vendor_name' => $vendor->name,
            ], $meta),
        ]);
    }
}