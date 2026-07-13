<?php

namespace App\Listeners\Boarding;

use App\Events\Boarding\HealthDischarged;
use App\Events\Boarding\HealthPermitApproved;
use App\Events\Boarding\LeaveApproved;
use App\Events\Boarding\LeaveReturned;
use App\Events\Boarding\RoomDamageReported;
use App\Services\NotificationUniversalService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Boarding → Notification Bus Bridge.
 *
 * Every boarding integration event has notification semantics:
 * - LeaveApproved       →  wali, dormitory_admin
 * - LeaveReturned       →  wali, dormitory_admin
 * - HealthPermitApproved →  wali, dormitory_admin, clinic
 * - HealthDischarged    →  wali, dormitory_admin, clinic
 * - RoomDamageReported  →  dormitory_admin, sarpras_admin
 *
 * This listener is the ONLY writer into NotificationUniversalService
 * for boarding events — keeping the cross-module surface narrow.
 */
class BroadcastBoardingNotificationToBus implements ShouldQueue
{
    public function __construct(
        private readonly NotificationUniversalService $notifier,
    ) {}

    public function handleLeaveApproved(LeaveApproved $event): void
    {
        $data = [
            'title' => 'Izin Pulang Disetujui',
            'message' => 'Permohonan izin pulang disetujui.',
            'type' => 'boarding.leave.approved',
            'payload' => [
                'permit_id' => $event->permit->id,
                'student_id' => $event->student->id,
                'student_name' => $event->student->name ?? null,
                'departure_datetime' => $event->permit->departure_datetime ?? null,
                'expected_return_datetime' => $event->permit->expected_return_datetime ?? null,
                'purpose' => $event->permit->purpose ?? null,
            ],
        ];

        if ($waliId = $event->student->wali_id ?? null) {
            $this->notifier->send($waliId, $data);
        }
        $this->notifier->sendToRole('dormitory_admin', $data);
    }

    public function handleLeaveReturned(LeaveReturned $event): void
    {
        $data = [
            'title' => 'Santri Telah Kembali',
            'message' => 'Santri telah kembali dari izin pulang.',
            'type' => 'boarding.leave.returned',
            'payload' => [
                'permit_id' => $event->permit->id,
                'student_id' => $event->student->id,
                'student_name' => $event->student->name ?? null,
                'actual_return_datetime' => $event->permit->actual_return_datetime ?? null,
            ],
        ];

        if ($waliId = $event->student->wali_id ?? null) {
            $this->notifier->send($waliId, $data);
        }
        $this->notifier->sendToRole('dormitory_admin', $data);
    }

    public function handleHealthApproved(HealthPermitApproved $event): void
    {
        $data = [
            'title' => 'Izin Sakit Disetujui',
            'message' => 'Permohonan izin sakit disetujui.',
            'type' => 'boarding.health.approved',
            'payload' => [
                'permit_id' => $event->permit->id,
                'student_id' => $event->student->id,
                'student_name' => $event->student->name ?? null,
                'permit_type' => $event->permit->permit_type,
                'start_date' => $event->permit->start_date,
                'end_date' => $event->permit->end_date,
            ],
        ];

        if ($waliId = $event->student->wali_id ?? null) {
            $this->notifier->send($waliId, $data);
        }
        $this->notifier->sendToRole('dormitory_admin', $data);
        $this->notifier->sendToRole('clinic_staff', $data);
    }

    public function handleHealthDischarged(HealthDischarged $event): void
    {
        $data = [
            'title' => 'Santri Sembuh',
            'message' => 'Santri telah sembuh dari sakit.',
            'type' => 'boarding.health.discharged',
            'payload' => [
                'permit_id' => $event->permit->id,
                'student_id' => $event->student->id,
                'student_name' => $event->student->name ?? null,
                'start_date' => $event->permit->start_date,
                'end_date' => $event->permit->end_date,
            ],
        ];

        if ($waliId = $event->student->wali_id ?? null) {
            $this->notifier->send($waliId, $data);
        }
        $this->notifier->sendToRole('dormitory_admin', $data);
        $this->notifier->sendToRole('clinic_staff', $data);
    }

    public function handleRoomDamage(RoomDamageReported $event): void
    {
        $data = [
            'title' => 'Kerusakan Kamar Dilaporkan',
            'message' => "Kerusakan kamar dilaporkan: {$event->damageType}",
            'type' => 'boarding.room.damage',
            'payload' => [
                'room_id' => $event->room->id,
                'damage_type' => $event->damageType,
                'severity' => $event->severity,
                'description' => $event->description,
            ],
        ];

        $this->notifier->sendToRole('dormitory_admin', $data);
        $this->notifier->sendToRole('sarpras_admin', $data);
    }
}