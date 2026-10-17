<?php

namespace App\Listeners;

use App\Events\StudentExitedFromRombel;
use App\Jobs\DeactivateStudentAcademicRecordsJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener ringan: menerima StudentExitedFromRombel dan mendispatch
 * DeactivateStudentAcademicRecordsJob ke queue academic-provision.
 *
 * Dengan menandai listener ini sebagai ShouldQueue, dispatch event
 * dari controller tidak memblokir request utama — semua logic berat
 * dieksekusi asynchronous oleh worker.
 *
 * Mengapa job dipisah (bukan logic langsung di listener)?
 *  - Idempotency dijamin oleh service (UPDATE-only, no INSERT)
 *  - Backoff/retry built-in untuk transient DB error
 *  - Listener tetap ramping — bisa jadi queue panjang tanpa degradasi
 */
class DeactivateStudentAcademicRecordsListener implements ShouldQueue
{
    public string $queue = 'academic-provision';

    public int $tries = 3;

    public int $backoff = 30;

    public function handle(StudentExitedFromRombel $event): void
    {
        // Guard: tolak event yang tidak membawa data wajib
        if (! $event->studentId || ! $event->studyGroupId || ! $event->academicYearId) {
            Log::warning('DeactivateStudentAcademicRecordsListener: event tanpa ID wajib', [
                'event' => $event,
            ]);

            return;
        }

        DeactivateStudentAcademicRecordsJob::dispatch(
            $event->studentId,
            $event->studyGroupId,
            $event->academicYearId,
            $event->leaveDate ?? now()->toDateString(),
            null, // semester di-resolve oleh job dari AcademicYear
            $event->classHistoryId,
            $event->reason,
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DeactivateStudentAcademicRecordsListener gagal mendispatch job', [
            'error' => $exception->getMessage(),
        ]);
    }
}
