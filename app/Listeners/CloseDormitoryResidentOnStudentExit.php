<?php

namespace App\Listeners;

use App\Events\StudentExitedFromRombel;
use App\Events\StudentGraduated;
use App\Events\StudentMutatedOut;
use App\Models\DormitoryResident;
use App\Models\Student;
use App\Services\NotificationUniversalService;
use Illuminate\Support\Facades\Log;

/**
 * Mutasi keluar / kelulusan / keluar rombel → tutup penghuni asrama aktif.
 * Data TIDAK dihapus: is_active=false + tanggal/alasan check-out tersimpan
 * sebagai riwayat yang dapat dilihat pada filter "Keluar".
 */
class CloseDormitoryResidentOnStudentExit
{
    public function handle(StudentMutatedOut $event): void
    {
        $this->close(
            studentId: $event->student->id,
            studentName: $event->student->name,
            reason: 'keluar',
            note: 'Mutasi keluar ('.$event->outType.')',
            date: $event->leaveDate,
        );
    }

    public function handleGraduated(StudentGraduated $event): void
    {
        $this->close(
            studentId: $event->student->id,
            studentName: $event->student->name,
            reason: 'lulus',
            note: 'Lulus ('.$event->graduationDate.')',
            date: $event->graduationDate,
        );
    }

    public function handleExitedFromRombel(StudentExitedFromRombel $event): void
    {
        $student = Student::find($event->studentId);
        if (! $student) {
            return;
        }

        $this->close(
            studentId: $student->id,
            studentName: $student->name,
            reason: $event->reason === 'graduated' ? 'lulus' : 'keluar',
            note: 'Keluar rombel: '.$event->reason,
            date: $event->leaveDate,
        );
    }

    private function close(string $studentId, string $studentName, string $reason, string $note, ?string $date): void
    {
        try {
            $residents = DormitoryResident::query()
                ->where('student_id', $studentId)
                ->where('is_active', true)
                ->get();

            foreach ($residents as $resident) {
                $existingNote = trim((string) $resident->notes);
                $resident->update([
                    'is_active' => false,
                    'check_out_date' => $date ?: now()->toDateString(),
                    'check_out_reason' => $reason,
                    'notes' => $existingNote !== '' ? $existingNote.' | '.$note : $note,
                ]);

                app(NotificationUniversalService::class)->notifyDormitoryStaff($resident->dormitory_id, [
                    'module' => 'dormitory',
                    'reference_type' => 'dormitory_resident',
                    'reference_id' => $resident->id,
                    'type' => 'warning',
                    'action' => 'resident_closed_lifecycle',
                    'title' => 'Asrama: '.$studentName.' keluar',
                    'message' => $note.'. Kamar/ranjang otomatis dikosongkan; data tetap tersimpan sebagai riwayat.',
                    'priority' => 'medium',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Tutup penghuni asrama gagal: '.$e->getMessage());
        }
    }
}
