<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\GoodsReceipt;
use App\Models\Notification;
use App\Models\PurchaseOrder;
use App\Models\QualityCheck;
use App\Services\Sarpras\StateMachine;
use Illuminate\Support\Facades\DB;

class QualityCheckService
{
    public const TRANSITIONS = [
        'qc' => [
            QualityCheck::STATUS_PENDING => [
                QualityCheck::STATUS_IN_PROGRESS,
                QualityCheck::STATUS_CANCELLED,
            ],
            QualityCheck::STATUS_IN_PROGRESS => [
                QualityCheck::STATUS_PASSED,
                QualityCheck::STATUS_FAILED,
                QualityCheck::STATUS_PARTIALLY_PASSED,
                QualityCheck::STATUS_CANCELLED,
            ],
        ],
    ];

    public function __construct(private StateMachine $machine) {}

    public function create(
        PurchaseOrder $po,
        ?GoodsReceipt $gr = null,
        int $userId = 0,
        array $data = []
    ): QualityCheck {
        $qc = QualityCheck::create([
            'qc_number' => (new QualityCheck())->generateNumber(),
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $gr?->id,
            'status' => QualityCheck::STATUS_PENDING,
            'inspection_date' => $data['inspection_date'] ?? now()->toDateString(),
            'inspector_id' => $data['inspector_id'] ?? null,
            'inspector_name' => $data['inspector_name'] ?? null,
            'inspection_criteria' => $data['inspection_criteria'] ?? null,
            'sample_size' => $data['sample_size'] ?? 0,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->audit($qc, 'created');

        return $qc;
    }

    public function start(QualityCheck $qc, string $inspectorId, string $inspectorName): QualityCheck
    {
        $this->machine->assert('qc', $qc->status, QualityCheck::STATUS_IN_PROGRESS);

        $qc->update([
            'status' => QualityCheck::STATUS_IN_PROGRESS,
            'inspector_id' => $inspectorId,
            'inspector_name' => $inspectorName,
        ]);

        $this->audit($qc, 'started');

        return $qc;
    }

    public function recordResults(QualityCheck $qc, array $results): QualityCheck
    {
        if (!in_array($qc->status, [QualityCheck::STATUS_IN_PROGRESS], true)) {
            throw new \InvalidArgumentException('Cannot record results in current state.');
        }

        $qc->update([
            'inspection_results' => $results,
            'passed_quantity' => $results['passed_quantity'] ?? $qc->passed_quantity,
            'failed_quantity' => $results['failed_quantity'] ?? $qc->failed_quantity,
            'sample_size' => $results['sample_size'] ?? $qc->sample_size,
        ]);

        $qc->recalculatePassRate();
        $qc->save();

        $this->audit($qc, 'results_recorded');

        return $qc;
    }

    public function pass(QualityCheck $qc, ?string $notes = null): QualityCheck
    {
        $this->machine->assert('qc', $qc->status, QualityCheck::STATUS_PASSED);

        $qc->update([
            'status' => QualityCheck::STATUS_PASSED,
            'completed_at' => now(),
            'notes' => $notes ? ($qc->notes . "\n[Pass] {$notes}") : $qc->notes,
        ]);

        $this->notifyCompleted($qc, 'passed');
        $this->audit($qc, 'passed');

        return $qc;
    }

    public function fail(QualityCheck $qc, string $failureReasons, ?string $recommendations = null): QualityCheck
    {
        $this->machine->assert('qc', $qc->status, QualityCheck::STATUS_FAILED);

        $qc->update([
            'status' => QualityCheck::STATUS_FAILED,
            'completed_at' => now(),
            'failure_reasons' => $failureReasons,
            'recommendations' => $recommendations,
        ]);

        $this->notifyCompleted($qc, 'failed');
        $this->audit($qc, 'failed');

        return $qc;
    }

    public function partiallyPass(QualityCheck $qc, string $failureReasons, ?string $recommendations = null): QualityCheck
    {
        $this->machine->assert('qc', $qc->status, QualityCheck::STATUS_PARTIALLY_PASSED);

        $qc->update([
            'status' => QualityCheck::STATUS_PARTIALLY_PASSED,
            'completed_at' => now(),
            'failure_reasons' => $failureReasons,
            'recommendations' => $recommendations,
        ]);

        $this->notifyCompleted($qc, 'partially_passed');
        $this->audit($qc, 'partially_passed');

        return $qc;
    }

    public function cancel(QualityCheck $qc, string $reason): QualityCheck
    {
        $this->machine->assert('qc', $qc->status, QualityCheck::STATUS_CANCELLED);

        $qc->update([
            'status' => QualityCheck::STATUS_CANCELLED,
            'notes' => $qc->notes . "\n[Cancelled] {$reason}",
        ]);

        $this->audit($qc, 'cancelled', ['reason' => $reason]);

        return $qc;
    }

    public function getStatistics(?string $vendorId = null): array
    {
        $query = QualityCheck::query()->whereIn('status', [
            QualityCheck::STATUS_PASSED,
            QualityCheck::STATUS_FAILED,
            QualityCheck::STATUS_PARTIALLY_PASSED,
        ]);

        if ($vendorId) {
            $query->whereHas('purchaseOrder', fn ($q) => $q->where('vendor_id', $vendorId));
        }

        $total = $query->count();
        $passed = $query->where('status', QualityCheck::STATUS_PASSED)->count();
        $failed = $query->where('status', QualityCheck::STATUS_FAILED)->count();
        $partial = $query->where('status', QualityCheck::STATUS_PARTIALLY_PASSED)->count();

        return [
            'total_inspections' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'partially_passed' => $partial,
            'pass_rate' => $total > 0 ? round(($passed / $total) * 100, 2) : 0,
        ];
    }

    private function notifyCompleted(QualityCheck $qc, string $result): void
    {
        if (!$qc->purchaseOrder) {
            return;
        }

        Notification::create([
            'user_id' => $qc->purchaseOrder->created_by,
            'type' => "qc_{$result}",
            'title' => 'Quality Check Completed',
            'message' => "QC {$qc->qc_number} completed: {$result}",
            'data' => ['qc_id' => $qc->id, 'po_id' => $qc->purchase_order_id, 'result' => $result],
        ]);
    }

    private function audit(QualityCheck $qc, string $action, array $meta = []): void
    {
        AuditTrail::create([
            'actor_id' => auth()->id(),
            'action' => "qc.{$action}",
            'entity_type' => QualityCheck::class,
            'entity_id' => $qc->id,
            'metadata' => array_merge([
                'qc_number' => $qc->qc_number,
                'status' => $qc->status,
            ], $meta),
        ]);
    }
}