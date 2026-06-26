<?php

namespace App\Listeners;

use App\Events\StudentAssignedToRombel;
use App\Jobs\ProvisionStudentAcademicDataJob;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener untuk event StudentAssignedToRombel.
 *
 * TIDAK berisi logic provisioning — tugasnya hanya mendelegasikan ke
 * ProvisionStudentAcademicDataJob yang berjalan di queue terpisah.
 *
 * Listener ini synchronous (implements ShouldQueue bukan dari base class),
 * sehingga trigger event dari controller tetap ringan: controller cukup
 * event() / dispatch() dan request selesai.
 */
class ProvisionStudentAcademicDataListener implements ShouldQueue
{
    /**
     * Listener di-queue agar dispatch dari controller benar-benar non-blocking.
     * Job berat tetap jalan di queue khusus 'academic-provision'.
     */
    public string $queue = 'academic-provision';

    public function handle(StudentAssignedToRombel $event): void
    {
        ProvisionStudentAcademicDataJob::dispatch(
            $event->studentId,
            $event->studyGroupId,
            $event->academicYearId,
            $event->joinDate,
            $event->semester,
            $event->classHistoryId,
        );
    }
}
