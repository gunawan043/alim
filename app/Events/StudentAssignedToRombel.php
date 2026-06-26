<?php

namespace App\Events;

use App\Models\StudentClassHistory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event yang dipicu ketika seorang siswa berhasil dimasukkan ke sebuah rombel
 * (StudentClassHistory dengan is_active = true).
 *
 * Event ini TIDAK berisi logic bisnis — hanya kontrak data untuk listener.
 * Disimpan ke payload queue sehingga listener dapat di-restart tanpa takut
 * kehilangan konteks eksekusi (SerializesModels).
 */
class StudentAssignedToRombel
{
    use Dispatchable, SerializesModels;

    /**
     * Identifier StudentClassHistory yang baru aktif (primary key histori).
     * Disimpan sebagai UUID string untuk idempotency di level job & service.
     */
    public string $classHistoryId;

    /**
     * Snapshot ringan untuk menghindari listener melakukan query tambahan
     * terhadap model pada saat serialisasi queue.
     */
    public string $studentId;

    public string $studyGroupId;

    public string $academicYearId;

    public ?string $semester;

    public string $joinDate;

    /**
     * Konstruktor menerima tiga jenis input:
     *  - StudentClassHistory model (preferable): snapshot otomatis
     *  - 3 string UUID: (studentId, studyGroupId, academicYearId)
     *    dipakai oleh controller yang sudah punya ID di tangan (mis.
     *    StudyGroupApiController AJAX handler).
     *
     * @param  StudentClassHistory|string  $studentOrId
     */
    public function __construct(
        $studentOrId,
        ?string $studyGroupId = null,
        ?string $academicYearId = null
    ) {
        if ($studentOrId instanceof StudentClassHistory) {
            $classHistory = $studentOrId;
            $this->classHistoryId = (string) $classHistory->id;
            $this->studentId = (string) $classHistory->student_id;
            $this->studyGroupId = (string) $classHistory->study_group_id;
            $this->academicYearId = (string) $classHistory->academic_year_id;
            $this->joinDate = optional($classHistory->join_date)->toDateString() ?? now()->toDateString();
            $this->semester = $this->resolveSemester($classHistory);

            return;
        }

        // Mode 3-argumen: ID langsung dari controller
        $this->classHistoryId = ''; // unknown at dispatch time → job resolves via lookup
        $this->studentId = (string) $studentOrId;
        $this->studyGroupId = (string) $studyGroupId;
        $this->academicYearId = (string) $academicYearId;
        $this->joinDate = now()->toDateString();
        $this->semester = $this->resolveSemesterByLookup($this->academicYearId);
    }

    /**
     * Semester berasal dari AcademicYear. Fallback ke 'ganjil' agar tetap
     * deterministik jika AcademicYear tidak ditemukan.
     */
    private function resolveSemester(StudentClassHistory $classHistory): string
    {
        $ay = $classHistory->relationLoaded('academicYear')
            ? $classHistory->academicYear
            : $classHistory->academicYear()->first();

        return $ay?->semester ?? 'ganjil';
    }

    private function resolveSemesterByLookup(string $academicYearId): string
    {
        static $cache = [];
        if (! isset($cache[$academicYearId])) {
            $cache[$academicYearId] = \App\Models\AcademicYear::whereKey($academicYearId)->value('semester');
        }

        return $cache[$academicYearId] ?? 'ganjil';
    }
}
