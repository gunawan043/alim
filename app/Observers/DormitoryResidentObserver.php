<?php

namespace App\Observers;

use App\Models\DormitoryResident;
use App\Models\School;
use App\Models\Student;

/**
 * Sinkronisasi boarding_status santri dari data penghuni asrama:
 *  - penghuni aktif (ditempatkan di kamar) → boarding
 *  - penghuni nonaktif / dihapus (keluar asrama) → non_boarding
 * Hanya berlaku untuk satuan pendidikan boarding/both (asrama menang).
 */
class DormitoryResidentObserver
{
    public function saved(DormitoryResident $resident): void
    {
        $this->sync($resident->student_id, (bool) $resident->is_active);
    }

    public function deleted(DormitoryResident $resident): void
    {
        $this->sync($resident->student_id, false);
    }

    private function sync(?string $studentId, bool $active): void
    {
        if (! $studentId) {
            return;
        }

        $student = Student::find($studentId);

        if (! $student) {
            return;
        }

        $school = School::find($student->school_id);

        // Satuan non-boarding tidak memiliki asrama — jangan mengubah status.
        if ($school && $school->boarding_type === 'non_boarding') {
            return;
        }

        $student->forceFill([
            'boarding_status' => $active ? 'boarding' : 'non_boarding',
        ])->saveQuietly();
    }
}
