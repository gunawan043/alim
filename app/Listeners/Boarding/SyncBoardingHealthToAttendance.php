<?php

namespace App\Listeners\Boarding;

use App\Events\Boarding\HealthDischarged;
use App\Events\Boarding\HealthPermitApproved;
use App\Models\IntegrationEventLog;
use Illuminate\Support\Facades\Log;

/**
 * Attend HealthPermitApproved & HealthDischarged events.
 * Marks student absent during hospitalization period, present on recovery.
 */
class SyncBoardingHealthToAttendance
{
    public function __construct(
        private readonly \App\Services\AcademicAttendanceSyncService $sync,
    ) {}

    public function handle(HealthPermitApproved $event): void
    {
        try {
            $startDate = $event->permit->start_date ?? now()->format('Y-m-d');
            $endDate = $event->permit->end_date ?? now()->format('Y-m-d');

            $this->sync->markAsAbsent(
                studentId: $event->student->id,
                startDate: $startDate,
                endDate: $endDate,
                source: 'boarding_health',
                sourceId: $event->permit->id,
            );

            IntegrationEventLog::record(
                eventName: 'attendance.sync.health_approved',
                sourceModule: 'boarding',
                targetModule: 'academic',
                aggregateId: $event->student->id,
                aggregateType: 'Student',
                payload: [
                    'permit_id' => $event->permit->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'permit_type' => $event->permit->permit_type,
                ],
            );
        } catch (\Throwable $e) {
            Log::error('SyncBoardingHealthToAttendance::handle failed', [
                'permit_id' => $event->permit->id,
                'error' => $e->getMessage(),
            ]);

            IntegrationEventLog::record(
                eventName: 'attendance.sync.health_approved',
                sourceModule: 'boarding',
                targetModule: 'academic',
                aggregateId: $event->student->id,
                aggregateType: 'Student',
                payload: ['error' => $e->getMessage()],
                status: IntegrationEventLog::STATUS_FAILED,
            );
        }
    }

    public function handleDischarge(HealthDischarged $event): void
    {
        try {
            $this->sync->markAsPresent(
                studentId: $event->student->id,
                date: now()->format('Y-m-d'),
                source: 'boarding_health_discharged',
                sourceId: $event->permit->id,
            );

            IntegrationEventLog::record(
                eventName: 'attendance.sync.health_discharged',
                sourceModule: 'boarding',
                targetModule: 'academic',
                aggregateId: $event->student->id,
                aggregateType: 'Student',
                payload: [
                    'permit_id' => $event->permit->id,
                    'date' => now()->format('Y-m-d'),
                ],
            );
        } catch (\Throwable $e) {
            Log::error('SyncBoardingHealthToAttendance::handleDischarge failed', [
                'permit_id' => $event->permit->id,
                'error' => $e->getMessage(),
            ]);

            IntegrationEventLog::record(
                eventName: 'attendance.sync.health_discharged',
                sourceModule: 'boarding',
                targetModule: 'academic',
                aggregateId: $event->student->id,
                aggregateType: 'Student',
                payload: ['error' => $e->getMessage()],
                status: IntegrationEventLog::STATUS_FAILED,
            );
        }
    }
}