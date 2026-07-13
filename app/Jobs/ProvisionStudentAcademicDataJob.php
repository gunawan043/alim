<?php

namespace App\Jobs;

use App\Models\StudentClassHistory;
use App\Services\AcademicProvisionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job utama yang menjalankan provisioning data akademik.
 *
 * Listener ProvisionStudentAcademicDataListener mendispatch job ini.
 * Job dapat dipicu berulang tanpa efek samping (idempotent — lihat
 * AcademicProvisionService).
 */
class ProvisionStudentAcademicDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $studentId;

    public string $studyGroupId;

    public string $academicYearId;

    public string $joinDate;

    public ?string $semester;

    public string $classHistoryId;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * Timeout total job dalam detik (default 5 menit).
     * Provisioning per siswa seharusnya < 1 detik, tapi timeout ini
     * memberi ruang aman untuk batch besar atau DB lambat.
     */
    public int $timeout = 300;

    public function __construct(
        string $studentId,
        string $studyGroupId,
        string $academicYearId,
        string $joinDate,
        ?string $semester = null,
        ?string $classHistoryId = null,
    ) {
        $this->studentId = $studentId;
        $this->studyGroupId = $studyGroupId;
        $this->academicYearId = $academicYearId;
        $this->joinDate = $joinDate;
        $this->semester = $semester;
        $this->classHistoryId = $classHistoryId ?? '';
    }

    public function handle(): void
    {
        // Guard: jika StudentClassHistory sudah non-aktif, abort.
        // Mencegah job yang terlambat memproses histori yang sudah kadaluarsa.
        if ($this->classHistoryId !== '') {
            $history = StudentClassHistory::find($this->classHistoryId);
            if ($history && ! $history->is_active) {
                Log::info('ProvisionStudentAcademicDataJob dilewati: histori tidak aktif', [
                    'class_history_id' => $this->classHistoryId,
                ]);

                return;
            }
        }

        $service = new AcademicProvisionService(
            $this->studentId,
            $this->studyGroupId,
            $this->academicYearId,
            $this->joinDate,
            $this->semester,
        );

        $result = $service->provision();

        Log::info('ProvisionStudentAcademicDataJob selesai', [
            'class_history_id' => $this->classHistoryId,
            'student_id' => $this->studentId,
            'study_group_id' => $this->studyGroupId,
            'result' => $result,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ProvisionStudentAcademicDataJob gagal permanen', [
            'class_history_id' => $this->classHistoryId,
            'student_id' => $this->studentId,
            'study_group_id' => $this->studyGroupId,
            'academic_year_id' => $this->academicYearId,
            'error' => $exception->getMessage(),
        ]);
    }
}
