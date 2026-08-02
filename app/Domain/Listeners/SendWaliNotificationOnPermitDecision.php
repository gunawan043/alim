<?php

namespace App\Domain\Listeners;

use App\Domain\Events\BoardingPermitDecided;
use App\Models\NotificationUniversal;
use App\Models\WaliSantri;

/**
 * Kirim notifikasi ke wali ketika izin disetujui/ditolak.
 */
class SendWaliNotificationOnPermitDecision
{
    public function handle(BoardingPermitDecided $event): void
    {
        $permit = $event->permit;
        $student = $permit->student;
        if (! $student) {
            return;
        }

        // Find wali yang terhubung dengan student ini
        $wals = WaliSantri::where('student_id', $student->id)
            ->where('status', WaliSantri::STATUS_ACTIVE)
            ->whereNotNull('user_id')
            ->get();

        if ($wals->isEmpty()) {
            return;
        }

        $title = match ($event->decision) {
            'approved' => 'Permohonan Izin Diterima',
            'rejected' => 'Permohonan Izin Ditolak',
            default => 'Update Permohonan Izin',
        };

        $type = match ($event->decision) {
            'approved' => 'success',
            'rejected' => 'error',
            default => 'info',
        };

        foreach ($wals as $wali) {
            $message = match ($event->decision) {
                'approved' => "Permohonan izin pulang '{$permit->permit_type}' untuk {$student->name} telah disetujui.",
                'rejected' => 'Permohonan izin pulang \''.$permit->permit_type.'\' untuk '.$student->name.' ditolak.',
                default => 'Permohonan izin untuk '.$student->name.' diperbarui.',
            };
            if ($event->decision === 'rejected' && $event->note) {
                $message .= ' Alasan: '.$event->note;
            }

            NotificationUniversal::create([
                'user_id' => $wali->user_id,
                'module' => 'boarding',
                'type' => 'permit_decision',
                'action' => $event->decision,
                'title' => $title,
                'message' => $message,
                'reference_type' => \App\Models\DormitoryPermit::class,
                'reference_id' => $permit->id,
                'action_url' => route('user.asrama.approval-center', [
                    'userId' => $wali->user_id,
                    'asramaUuid' => $permit->dormitory_id,
                ]),
                'action_text' => 'Lihat Detail',
                'is_read' => false,
                'priority' => $event->decision === 'rejected' ? 'high' : 'medium',
            ]);
        }
    }
}
