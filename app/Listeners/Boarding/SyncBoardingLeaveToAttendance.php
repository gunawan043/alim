<?php

namespace App\Listeners\Boarding;

use App\Events\Boarding\LeaveApproved;
use App\Events\Boarding\LeaveReturned;
use App\Models\IntegrationEventLog;
use Illuminate\Support\Facades\Log;

/**
 * Attend Boarding LeaveApproved & LeaveReturned events.
 *
 * This listener MUST NOT touch any academic attendance table directly.
 * Instead it fires an Academic Attendance domain event via a
 * facade/interface, letting the Academic module decide how to
 * persist its own attendance records.
 *
 * Architecture:
 *   LeaveApproved / LeaveReturned  →  event  →  AcademicAttendanceSyncService
 *                                                                 │
 *                          [Academic Attendance Table] ←── writes ONLY from Academic context
 */
class SyncBoardingLeaveToAttendance
{
    public function __construct(
        private readonly \App\Services\AcademicAttendanceSyncService $sync,
    ) {}

    public function handle(LeaveApproved $event): void
    {
        try {
            $this->sync->markAsAbsent(
                studentId: $event->student->id,
                startDate: $event->permit->departure_datetime,
                endDate: $event->permit->expected_return_datetime,
                source: 'boarding_leave',
                sourceId: $event->permit->id,
            );

            IntegrationEventLog::record(
                eventName: 'attendance.sync.leave_approved',
                sourceModule: 'boarding',
                targetModule: 'academic',
                aggregateId: $event->student->id,
                aggregateType: 'Student',
                payload: [
                    'permit_id' => $event->permit->id,
                    'departure_datetime' => $event->permit->departure_datetime,
                    'expected_return_datetime' => $event->permit->expected_return_datetime,
                ],
            );
        } catch (\Throwable $e) {
            Log::error('SyncBoardingLeaveToAttendance::handle failed', [
                'permit_id' => $event->permit->id,
                'student_id' => $event->student->id,
                'error' => $e->getMessage(),
            ]);

            IntegrationEventLog::record(
                eventName: 'attendance.sync.leave_approved',
                sourceModule: 'boarding',
                targetModule: 'academic',
                aggregateId: $event->student->id,
                aggregateType: 'Student',
                payload: [
                    'permit_id' => $event->permit->id,
                    'error' => $e->getMessage(),
                ],
                status: IntegrationEventLog::STATUS_FAILED,
            );
        }
    }

    public function handleReturn(LeaveReturned $event): void
    {
        try {
            // Mark attendance resumed from return date
            $this->sync->markAsPresent(
                studentId: $event->student->id,
                date: $event->permit->actual_return_datetime,
                source: 'boarding_return',
                sourceId: $event->permit->id,
            );

            IntegrationEventLog::record(
                eventName: 'attendance.sync.leave_returned',
                sourceModule: 'boarding',
                targetModule: 'academic',
                aggregateId: $event->student->id,
                aggregateType: 'Student',
                payload: [
                    'permit_id' => $event->permit->id,
                    'return_date' => $event->permit->actual_return_datetime,
                ],
            );
        } catch (\Throwable $e) {
            Log::error('SyncBoardingLeaveToAttendance::handleReturn failed', [
                'permit_id' => $event->permit->id,
                'student_id' => $event->student->id,
                'error' => $e->getMessage(),
            ]);

            IntegrationEventLog::record(
                eventName: 'attendance.sync.leave_returned',
                sourceModule: 'boarding',
                targetModule: 'academic',
                aggregateId: $event->student->id,
                aggregateType: 'Student',
                payload: [
                    'permit_id' => $event->permit->id,
                    'error' => $e->getMessage(),
                ],
                status: IntegrationEventLog::STATUS_FAILED,
            );
        }
    }
}
