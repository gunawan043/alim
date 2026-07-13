<?php

namespace App\Listeners\Boarding;

use App\Events\Boarding\HealthDischarged;
use App\Events\Boarding\HealthPermitApproved;
use App\Models\IntegrationEventLog;
use App\Services\Clinic\ClinicSyncService;
use Illuminate\Support\Facades\Log;

/**
 * Sync boarding health permit approval/discharge to the Clinic module.
 *
 * Boarding triggers the event (because the parent-approval workflow lives
 * in boarding), but the Clinic module is the system-of-record for
 * clinical records (diagnosis, treatment, prescriptions).
 */
class SyncHealthToClinic
{
    public function __construct(
        private readonly ClinicSyncService $clinic,
    ) {}

    public function handle(HealthPermitApproved $event): void
    {
        try {
            $clinicVisitId = $this->clinic->openVisitFromBoardingPermit(
                studentId: $event->student->id,
                boardingPermitId: $event->permit->id,
                permitType: $event->permit->permit_type,
                startDate: $event->permit->start_date,
                endDate: $event->permit->end_date,
                notes: $event->permit->description ?? null,
            );

            IntegrationEventLog::record(
                eventName: 'clinic.sync.opened_from_boarding',
                sourceModule: 'boarding',
                targetModule: 'clinic',
                aggregateId: $event->student->id,
                aggregateType: 'Student',
                payload: [
                    'permit_id' => $event->permit->id,
                    'clinic_visit_id' => $clinicVisitId,
                ],
            );
        } catch (\Throwable $e) {
            Log::error('SyncHealthToClinic::handle failed', [
                'permit_id' => $event->permit->id,
                'error' => $e->getMessage(),
            ]);

            IntegrationEventLog::record(
                eventName: 'clinic.sync.opened_from_boarding',
                sourceModule: 'boarding',
                targetModule: 'clinic',
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
            $this->clinic->dischargeVisitFromBoardingPermit(
                studentId: $event->student->id,
                boardingPermitId: $event->permit->id,
                dischargeNotes: $event->permit->approval_note ?? null,
            );

            IntegrationEventLog::record(
                eventName: 'clinic.sync.discharged_from_boarding',
                sourceModule: 'boarding',
                targetModule: 'clinic',
                aggregateId: $event->student->id,
                aggregateType: 'Student',
                payload: ['permit_id' => $event->permit->id],
            );
        } catch (\Throwable $e) {
            Log::error('SyncHealthToClinic::handleDischarge failed', [
                'permit_id' => $event->permit->id,
                'error' => $e->getMessage(),
            ]);

            IntegrationEventLog::record(
                eventName: 'clinic.sync.discharged_from_boarding',
                sourceModule: 'boarding',
                targetModule: 'clinic',
                aggregateId: $event->student->id,
                aggregateType: 'Student',
                payload: ['error' => $e->getMessage()],
                status: IntegrationEventLog::STATUS_FAILED,
            );
        }
    }
}