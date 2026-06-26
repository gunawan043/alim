<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\RaportRegistration;
use App\Models\StudentAbsence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service untuk provisioning data akademik secara idempotent per siswa.
 *
 * Idempotency dicapai dengan:
 *   - student_absences  : unique (student_id, study_group_id, academic_year_id, semester)
 *   - raport_registrations : unique (student_id, study_group_id, academic_year_id, semester)
 *   - admin_nilai_sumatif : updateOrCreate per (admin_book_id, student_id, semester)
 *
 * Jika event ter-trigger dua kali, semua operasi akan aman melalui updateOrCreate.
 */
class AcademicProvisionService
{
    protected string $studentId;

    protected string $studyGroupId;

    protected string $academicYearId;

    protected string $joinDate;

    protected string $semester;

    public function __construct(
        string $studentId,
        string $studyGroupId,
        string $academicYearId,
        string $joinDate,
        ?string $semester = null
    ) {
        $this->studentId = $studentId;
        $this->studyGroupId = $studyGroupId;
        $this->academicYearId = $academicYearId;
        $this->joinDate = $joinDate;

        if ($semester) {
            $this->semester = $semester;
        } else {
            $this->semester = $this->determineSemester();
        }
    }

    // -----------------------------------------------------------------------
    // Entry point — orchestrates all provisioning steps
    // -----------------------------------------------------------------------

    /**
     * Jalankan seluruh provisioning secara atom.
     *
     * Returns array['student_absence' => bool, 'raport_registrations' => int, 'nilai_sumatif' => int].
     */
    public function provision(): array
    {
        $counts = ['student_absence' => 0, 'raport_registrations' => 0, 'nilai_sumatif' => 0];

        DB::beginTransaction();
        try {
            $counts['student_absence'] = $this->provisionStudentAbsence();
            $counts['raport_registrations'] = $this->provisionRaportRegistrations();
            $counts['nilai_sumatif'] = $this->provisionNilaiSumatifPlaceholders();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('AcademicProvisionService gagal', [
                'student_id' => $this->studentId,
                'study_group_id' => $this->studyGroupId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return $counts;
    }

    // -----------------------------------------------------------------------
    // Step 1 — Registrasi absensi siswa (student_absences)
    // -----------------------------------------------------------------------

    /**
     * Buat atau update record student_absences (enrollment ke sistem absensi).
     *
     * Idempotent — return 1 (dibuat) atau 0 (sudah ada, di-update).
     */
    public function provisionStudentAbsence(): int
    {
        $existing = StudentAbsence::where(
            'student_id', $this->studentId
        )->where('study_group_id', $this->studyGroupId)
            ->where('academic_year_id', $this->academicYearId)
            ->where('semester', $this->semester)
            ->first();

        if ($existing) {
            return 0; // Sudah ada, bukan duplikasi
        }

        StudentAbsence::create([
            'student_id' => $this->studentId,
            'study_group_id' => $this->studyGroupId,
            'academic_year_id' => $this->academicYearId,
            'semester' => $this->semester,
            'enrollment_status' => 'active',
            'enrolled_at' => $this->joinDate,
        ]);

        return 1;
    }

    // -----------------------------------------------------------------------
    // Step 2 — Registrasi rapor (raport_registrations)
    // -----------------------------------------------------------------------

    /**
     * Daftarkan siswa ke pipeline rapor untuk semester ini.
     *
     * Karena rapor bersifat agregat per (siswa, rombel, TA, semester), cukup
     * satu row yang menandai siswa sudah masuk pipeline. Nilai per-mapel
     * tetap disusun saat pencetakan melalui query ke admin_nilai_sumatif.
     *
     * Returns 1 jika row baru dibuat, 0 jika sudah ada (idempotent).
     */
    public function provisionRaportRegistrations(): int
    {
        $existing = RaportRegistration::where('student_id', $this->studentId)
            ->where('study_group_id', $this->studyGroupId)
            ->where('academic_year_id', $this->academicYearId)
            ->where('semester', $this->semester)
            ->first();

        if ($existing) {
            return 0;
        }

        RaportRegistration::create([
            'student_id' => $this->studentId,
            'study_group_id' => $this->studyGroupId,
            'academic_year_id' => $this->academicYearId,
            'semester' => $this->semester,
            'status' => 'draft',
        ]);

        return 1;
    }

    // -----------------------------------------------------------------------
    // Step 3 — Placeholder nilai sumatif per mapel aktif
    // -----------------------------------------------------------------------

    /**
     * Buat placeholder nilai sumatif untuk setiap mapel aktif.
     * Hanya memastikan row ada di admin_nilai_sumatif; semua field nilai tetap null.
     * Returns jumlah placeholder yang dibuat.
     */
    public function provisionNilaiSumatifPlaceholders(): int
    {
        // Ambil admin book IDs aktif di rombel
        $activeBookIds = DB::table('teacher_admin_books')
            ->where('study_group_id', $this->studyGroupId)
            ->where('academic_year_id', $this->academicYearId)
            ->where('semester', $this->semester)
            ->where('is_active', true)
            ->pluck('id');

        $created = 0;
        foreach ($activeBookIds as $bookId) {
            $exists = DB::table('admin_nilai_sumatif')
                ->where('admin_book_id', $bookId)
                ->where('student_id', $this->studentId)
                ->where('semester', $this->semester)
                ->exists();

            if (! $exists) {
                DB::table('admin_nilai_sumatif')->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'admin_book_id' => $bookId,
                    'student_id' => $this->studentId,
                    'academic_year_id' => $this->academicYearId,
                    'semester' => $this->semester,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created++;
            }
        }

        return $created;
    }

    // -----------------------------------------------------------------------
    // Deactivation (lifecycle exit) — inverse of provision
    // -----------------------------------------------------------------------

    /**
     * Deactivation dijalankan ketika StudentClassHistory di-nonaktifkan atau dihapus.
     *
     * Tujuan: menjaga konsistensi status akademik saat siswa keluar dari rombel,
     * tanpa menghapus data historis (audit trail).
     *
     * Idempotent — data yang sudah inactive tidak akan disentuh ulang.
     *
     * Returns array of affected counts.
     */
    public function deactivate(): array
    {
        $counts = ['student_absences' => 0, 'raport_registrations' => 0];

        DB::beginTransaction();
        try {
            $counts['student_absences'] = $this->deactivateStudentAbsences();
            $counts['raport_registrations'] = $this->deactivateRaportRegistrations();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('AcademicProvisionService::deactivate gagal', [
                'student_id' => $this->studentId,
                'study_group_id' => $this->studyGroupId,
                'academic_year_id' => $this->academicYearId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $counts;
    }

    /**
     * Set student_absences ke inactive untuk rombel + TA + semester ini.
     * Idempotent — hanya update jika masih active.
     */
    public function deactivateStudentAbsences(): int
    {
        return StudentAbsence::where('student_id', $this->studentId)
            ->where('study_group_id', $this->studyGroupId)
            ->where('academic_year_id', $this->academicYearId)
            ->where('semester', $this->semester)
            ->where('enrollment_status', 'active')
            ->update([
                'enrollment_status' => 'inactive',
                'unenrolled_at' => $this->joinDate,
            ]);
    }

    /**
     * Set raport_registrations ke 'withdrawn' untuk rombel + TA + semester ini.
     * Hanya untuk rapor yang masih draft/in_progress — yang sudah published
     * (riwayat rapor yang sah) tidak diubah, karena merupakan data historis.
     */
    public function deactivateRaportRegistrations(): int
    {
        return RaportRegistration::where('student_id', $this->studentId)
            ->where('study_group_id', $this->studyGroupId)
            ->where('academic_year_id', $this->academicYearId)
            ->where('semester', $this->semester)
            ->whereIn('status', ['draft', 'in_progress'])
            ->update([
                'status' => 'withdrawn',
            ]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function determineSemester(): string
    {
        $ay = AcademicYear::find($this->academicYearId);

        return $ay?->semester ?? 'ganjil';
    }
}
