<?php

namespace App\Domain\Listeners;

use App\Domain\Events\BoardingVisitDecided;
use App\Models\DormitoryVisitLog;
use App\Models\NotificationUniversal;
use App\Models\WaliSantri;

/**
 * Kirim notifikasi ke wali ketika penjengukan disetujui/ditolak.
 */
class SendWaliNotificationOnVisitDecision
{
    public function handle(BoardingVisitDecided $event): void
    {
        $visit = $event->visit;
        $student = $visit->student;
        if (! $student) {
            return;
        }

        $wals = WaliSantri::where('student_id', $student->id)
            ->where('status', WaliSantri::STATUS_ACTIVE)
            ->whereNotNull('user_id')
            ->get();

        if ($wals->isEmpty()) {
            return;
        }

        $isApproved = $event->decision === 'approved';

        foreach ($wals as $wali) {
            NotificationUniversal::create([
                'user_id' => $wali->user_id,
                'module' => 'boarding',
                'type' => 'visit_decision',
                'action' => $event->decision,
                'title' => $isApproved ? 'Penjengukan Diterima' : 'Penjengukan Ditolak',
                'message' => $isApproved
                    ? "Permohonan penjengukan untuk {$student->name} telah disetujui."
                    : "Permohonan penjengukan untuk {$student->name} ditolak.".($event->note ? " Alasan: {$event->note}" : ''),
                'reference_type' => DormitoryVisitLog::class,
                'reference_id' => $visit->id,
                'action_url' => route('user.asrama.approval-center', [
                    'userId' => $wali->user_id,
                    'asramaUuid' => $visit->dormitory_id,
                ]),
                'action_text' => 'Lihat Detail',
                'is_read' => false,
                'priority' => $isApproved ? 'low' : 'high',
            ]);
        }
    }
}
