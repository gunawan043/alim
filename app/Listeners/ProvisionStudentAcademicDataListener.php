<?php

namespace App\Listeners;

use App\Events\StudentAssignedToRombel;
use App\Jobs\ProvisionStudentAcademicDataJob;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener untuk event StudentAssignedToRombel.
 *
 * Listener ini queued (ShouldQueue) sehingga request controller tidak
 * menunggu provisioning selesai. Listener sangat tipis — hanya delegasi
 * ke ProvisionStudentAcademicDataJob yang berjalan di queue khusus
 * 'academic-provision'.
 *
 * Queue di-set via listener::$queue dan job::dispatch(...)->onQueue(...)
 * karena Queueable trait sudah memiliki property $queue — mendeklarasikan
 * ulang property $queue di Job akan menyebabkan konflik komposisi di PHP 8.
 */
class ProvisionStudentAcademicDataListener implements ShouldQueue
{
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
        )->onQueue('academic-provision');
    }
}
