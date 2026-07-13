<?php

namespace App\Services\Sarpras;

use App\Events\Sarpras\VendorEvaluationCompleted;
use App\Models\Vendor;
use App\Models\WorkOrder;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class VendorPerformanceService
{
    /**
     * Compute performance metrics for a vendor over a period.
     */
    public function computeMetrics(Vendor $vendor, string $startDate, string $endDate): array
    {
        $workOrders = WorkOrder::where('vendor_id', $vendor->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalOrders = $workOrders->count();
        $completed = $workOrders->whereIn('status', ['completed', 'closed'])->count();
        $onTime = $workOrders->filter(function ($wo) {
            return $wo->status === 'completed' &&
                $wo->scheduled_date &&
                $wo->actual_end &&
                $wo->actual_end->lte($wo->scheduled_date);
        })->count();

        $onTimePct = $completed > 0 ? ($onTime / $completed) * 100 : 0;
        $quality = $vendor->rating_avg;

        $purchaseOrders = PurchaseOrder::where('vendor_id', $vendor->id)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->get();
        $totalValue = $purchaseOrders->sum('total');

        // Response time minutes (avg)
        $responseTimes = $workOrders->filter(function ($wo) {
            return $wo->actual_start && $wo->start_date;
        })->map(function ($wo) {
            return $wo->start_date->diffInMinutes($wo->actual_start);
        });
        $avgResponse = $responseTimes->count() > 0 ? $responseTimes->avg() : 0;

        $penalty = 0;
        foreach ($workOrders as $wo) {
            if ($wo->scheduled_date && $wo->actual_end && $wo->actual_end->gt($wo->scheduled_date)) {
                $daysLate = $wo->actual_end->diffInDays($wo->scheduled_date);
                $penalty += $daysLate * 50000; // IDR 50k/day baseline
            }
        }

        $score = 0;
        $score += min(40, $onTimePct * 0.4);
        $score += min(40, $quality * 8);
        $score += min(20, max(0, 20 - ($avgResponse / 60)));

        $grade = match (true) {
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default => 'E',
        };

        return [
            'vendor_id' => $vendor->id,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'total_orders' => $totalOrders,
            'completed_orders' => $completed,
            'on_time_pct' => round($onTimePct, 2),
            'quality_avg' => (float) $quality,
            'response_time_avg_minutes' => round($avgResponse, 2),
            'total_value' => (float) $totalValue,
            'penalty_amount' => (float) $penalty,
            'grade' => $grade,
            'score' => round($score, 2),
            'blacklist_recommendation' => $grade === 'E' && $totalOrders >= 3,
        ];
    }

    /**
     * Recompute and persist evaluation.
     */
    public function saveEvaluation(Vendor $vendor, string $startDate, string $endDate): \App\Models\VendorEvaluation
    {
        $metrics = $this->computeMetrics($vendor, $startDate, $endDate);

        $evaluation = \App\Models\VendorEvaluation::updateOrCreate(
            [
                'vendor_id' => $vendor->id,
                'period_start' => $startDate,
                'period_end' => $endDate,
            ],
            array_intersect_key($metrics, array_flip([
                'total_orders', 'completed_orders', 'on_time_pct',
                'quality_avg', 'response_time_avg_minutes',
                'total_value', 'penalty_amount', 'grade',
            ])) + [
                'blacklist_recommendation' => $metrics['blacklist_recommendation'],
            ]
        );

        VendorEvaluationCompleted::dispatch($evaluation, $metrics);

        return $evaluation;
    }

    /**
     * Take a daily snapshot of vendor performance.
     */
    public function snapshotPerformance(): int
    {
        $count = 0;
        Vendor::where('status', 'active')->each(function ($vendor) use (&$count) {
            \App\Models\VendorPerformanceHistory::create([
                'vendor_id' => $vendor->id,
                'snapshot_date' => now()->toDateString(),
                'rating_avg' => $vendor->rating_avg,
                'rating_count' => $vendor->rating_count,
                'active_orders' => WorkOrder::where('vendor_id', $vendor->id)
                    ->whereIn('status', ['assigned', 'working'])->count(),
                'on_time_pct' => $this->computeMetrics($vendor, now()->subYear()->toDateString(), now()->toDateString())['on_time_pct'] ?? 0,
                'total_value_ytd' => PurchaseOrder::where('vendor_id', $vendor->id)
                    ->whereYear('order_date', now()->year)->sum('total'),
            ]);
            $count++;
        });
        return $count;
    }

    /**
     * Generate vendor rankings for dashboard.
     */
    public function rankings(int $limit = 10): array
    {
        return Vendor::query()
            ->withAvg('ratings as rating_avg_v', 'overall_score')
            ->orderByDesc('rating_avg')
            ->limit($limit)
            ->get()
            ->map(fn ($v) => [
                'vendor_id' => $v->id,
                'vendor_code' => $v->vendor_code,
                'name' => $v->name,
                'rating_avg' => (float) $v->rating_avg,
                'rating_count' => $v->rating_count,
                'status' => $v->status,
            ])
            ->toArray();
    }
}