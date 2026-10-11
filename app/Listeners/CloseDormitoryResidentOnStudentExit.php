<?php

namespace App\Listeners;

use App\Events\StudentGraduated;
use App\Events\StudentMutatedOut;
use App\Models\DormitoryResident;
use App\Services\NotificationUniversalService;
use Illuminate\Support\Facades\Log;

/**
 * Mutasi keluar / kelulusan → tutup penghuni asrama aktif:
 * kamar dikosongkan, status boarding menjadi non-boarding (via observer),
 * dan petugas asrama mendapat notifikasi in-app.
 */
class CloseDormitoryResidentOnStudentExit
{
    public function handle(StudentMutatedOut $event): void
    {
        $this->close(
            studentId: $event->student->id,
            studentName: $event->student->name,
            reason: 'Mutasi keluar ('.$event->outType.')',
            date: $event->leaveDate,
        );
    }

    public function handleGraduated(StudentGraduated $event): void
    {
        $this->close(
            studentId: $event->student->id,
            studentName: $event->student->name,
            reason: 'Lulus ('.$event->graduationDate.')',
            date: $event->graduationDate,
        );
    }

    private function close(string $studentId, string $studentName, string $reason, ?string $date): void
    {
        try {
            $residents = DormitoryResident::query()
                ->where('student_id', $studentId)
                ->where('is_active', true)
                ->get();

            foreach ($residents as $resident) {
                $resident->update([
                    'is_active' => false,
                    'check_out_date' => $date ?: now()->toDateString(),
                    'check_out_reason' => $reason,
                ]);

                app(NotificationUniversalService::class)->notifyDormitoryStaff($resident->dormitory_id, [
                    'module' => 'dormitory',
                    'reference_type' => 'dormitory_resident',
                    'reference_id' => $resident->id,
                    'type' => 'warning',
                    'action' => 'resident_closed_lifecycle',
                    'title' => 'Asrama: '.$studentName.' keluar',
                    'message' => $reason.'. Kamar/ranjang otomatis dikosongkan.',
                    'priority' => 'medium',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Tutup penghuni asrama gagal: '.$e->getMessage());
        }
    }
}
