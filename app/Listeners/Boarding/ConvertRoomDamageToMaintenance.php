<?php

namespace App\Listeners\Boarding;

use App\Events\Boarding\RoomDamageReported;
use App\Models\IntegrationEventLog;
use Illuminate\Support\Facades\Log;

/**
 * When a boarding room damage is reported, this listener converts it
 * into a Sarpras maintenance work order.
 *
 * This is the Boarding → Sarpras bridge. Boarding knows nothing about
 * maintenance types or technicians; Sarpras owns that.
 *
 * Flow:
 *   RoomDamageReported event  →  SarprasWorkOrderCreateService
 *                                          │
 *                           [Sarpras Work Order Table] ←── writes ONLY from Sarpras context
 */
class ConvertRoomDamageToMaintenance
{
    public function __construct(
        private readonly \App\Services\Sarpras\SarprasWorkOrderCreateService $workOrderService,
    ) {}

    public function handle(RoomDamageReported $event): void
    {
        try {
            // Sarpras Work Order Service handles all maintenance logic
            // This is a generic interface — the service lives in the
            // Sarpras module, ensuring no cross-module data leakage.
            if (class_exists(\App\Services\Sarpras\SarprasWorkOrderCreateService::class)) {
                $this->workOrderService->createFromBoardingEvent(
                    moduleId: $event->room->id,
                    payload: [
                        'damage_type' => $event->damageType,
                        'description' => $event->description,
                        'severity' => $event->severity,
                    ],
                    aggregateType: 'DormitoryRoom',
                );
            }

            // Log the integration
            IntegrationEventLog::record(
                eventName: 'sarpras.maintenance_created_from_boarding',
                sourceModule: 'boarding',
                targetModule: 'sarpras',
                aggregateId: $event->room->id,
                aggregateType: 'DormitoryRoom',
                payload: [
                    'damage_type' => $event->damageType,
                    'severity' => $event->severity,
                    'description' => $event->description,
                ],
            );
        } catch (\Throwable $e) {
            Log::error('ConvertRoomDamageToMaintenance failed', [
                'room_id' => $event->room->id,
                'error' => $e->getMessage(),
            ]);

            IntegrationEventLog::record(
                eventName: 'sarpras.maintenance_created_from_boarding',
                sourceModule: 'boarding',
                targetModule: 'sarpras',
                aggregateId: $event->room->id,
                aggregateType: 'DormitoryRoom',
                payload: [
                    'damage_type' => $event->damageType,
                    'error' => $e->getMessage(),
                ],
                status: IntegrationEventLog::STATUS_FAILED,
            );
        }
    }
}
