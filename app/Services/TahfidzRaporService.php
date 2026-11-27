<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\NilaiSumatif;
use App\Models\Subject;
use App\Models\TahfidzGradebookScore;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzSetoran;
use App\Models\TeacherAdminBook;
use App\Support\AcademicNilai;
use Carbon\Carbon;

class TahfidzRaporService
{
    public function __construct(private TahfidzSubjectResolver $resolver) {}

    /**
     * Apakah mapel memakai format penilaian tahfidz?
     *
     * Diteruskan ke TahfidzSubjectResolver agar rapor, jadwal, leger, dan
     * sidebar memakai satu sumber kebenaran (kolom `penilaian_format`).
     */
    public function isTahfidzSubject(?Subject $subject): bool
    {
        return $this->resolver->isTahfidz($subject);
    }

    /**
     * Nilai mapel tahfidz untuk rapor.
     *
     * Sumbernya BUKAN rata-rata setoran lagi, melainkan buku nilai halaqah
     * (kolom Ziyadah, Rabt, STS, SAS yang diisi guru). Buku itu diikat ke
     * halaqah, sedangkan rapor tetap per rombel — karena itu nilai dibaca
     * per santri dari buku halaqah tempat ia terdaftar pada periode ini.
     *
     * `$row` (nilai sumatif buku mapel) tidak dipakai untuk tahfidz: format
     * tahfidz punya kolomnya sendiri dan tidak boleh tercampur dengan format
     * mapel umum.
     */
    public function scoreFor(
        string $studentId,
        TeacherAdminBook $book,
        string $academicYearId,
        string $semester,
        string $jenis,
        ?NilaiSumatif $row = null,
    ): ?float {
        if (! $this->isTahfidzSubject($book->subject)) {
            return AcademicNilai::resolveTampil($jenis, $row);
        }

        $score = TahfidzGradebookScore::query()
            ->whereHas('gradebook', fn ($q) => $q
                ->where('subject_id', $book->subject_id)
                ->where('academic_year_id', $academicYearId)
                ->where('semester', $semester)
                ->where('is_active', true))
            ->where('student_id', $studentId)
            ->first();

        if (! $score) {
            return null;
        }

        // Leger menampilkan SATU angka untuk mapel tahfidz — NR Final —
        // baik mode STS maupun SAS, agar sebanding dengan mapel umum.
        // STS hanya dipakai bila NR Final belum terbentuk.
        return $score->nr_final !== null
            ? (float) $score->nr_final
            : ($score->sts !== null ? (float) $score->sts : null);
    }

    public function jumlahJuz(string $studentId, string $academicYearId): int
    {
        return (int) TahfidzJuzProgress::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])
            ->count();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function semesterRange(AcademicYear $academicYear, string $semester): array
    {
        $start = Carbon::parse($academicYear->start_date)->startOfMonth();
        $end = $academicYear->end_date
            ? Carbon::parse($academicYear->end_date)->endOfMonth()
            : $start->copy()->addMonths(11)->endOfMonth();

        if ($semester === 'ganjil') {
            return [$start, $start->copy()->addMonths(5)->endOfMonth()->min($end)];
        }

        return [$start->copy()->addMonths(6)->startOfMonth()->max($start), $end];
    }
}
