<?php

namespace App\Services\Sarpras;

use App\Models\IntegrationEventLog;
use Illuminate\Support\Str;

/**
 * Sarpras Work Order Create Service.
 *
 * Single point of entry for creating work orders from cross-module
 * events (boarding room damage, clinic equipment damage, etc).
 *
 * This is the Sarpras side of the integration boundary: any module
 * wanting to schedule maintenance MUST go through this service.
 */
class SarprasWorkOrderCreateService
{
    public function createFromBoardingEvent(
        string $moduleId,
        array $payload,
        string $aggregateType = 'DormitoryRoom',
    ): string {
        $workOrderId = (string) Str::uuid();

        IntegrationEventLog::record(
            eventName: 'sarpras.work_order_created',
            sourceModule: 'boarding',
            targetModule: 'sarpras',
            aggregateId: $workOrderId,
            aggregateType: 'WorkOrder',
            payload: [
                'source_module_id' => $moduleId,
                'source_aggregate_type' => $aggregateType,
                'damage_type' => $payload['damage_type'] ?? null,
                'description' => $payload['description'] ?? null,
                'severity' => $payload['severity'] ?? null,
            ],
        );

        // Concrete Sarpras models (if present) handle persistence.
        // We keep this loose to avoid hard dependency.
        if (class_exists(\App\Models\Sarpras\WorkOrder::class)) {
            \App\Models\Sarpras\WorkOrder::create([
                'id' => $workOrderId,
                'source' => 'boarding',
                'source_id' => $moduleId,
                'source_aggregate_type' => $aggregateType,
                'damage_type' => $payload['damage_type'] ?? null,
                'description' => $payload['description'] ?? null,
                'severity' => $payload['severity'] ?? 'normal',
                'status' => 'open',
            ]);
        }

        return $workOrderId;
    }
}
