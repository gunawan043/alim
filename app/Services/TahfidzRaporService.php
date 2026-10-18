<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\NilaiSumatif;
use App\Models\Subject;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzSetoran;
use App\Models\TeacherAdminBook;
use App\Support\AcademicNilai;
use Carbon\Carbon;

class TahfidzRaporService
{
    public function isTahfidzSubject(?Subject $subject): bool
    {
        if (! $subject) {
            return false;
        }

        $haystack = mb_strtolower(trim(($subject->code ?? '').' '.($subject->name ?? '')));

        return str_contains($haystack, 'thf')
            || (bool) preg_match('/tahfidz|tahfiz|hafalan qur|hafalan al-qur/', $haystack);
    }

    public function setoranAverage(string $studentId, string $academicYearId, string $semester): ?float
    {
        $academicYear = AcademicYear::find($academicYearId);
        $query = TahfidzSetoran::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('verification_status', 'verified')
            ->where('status', 'lulus')
            ->whereNotNull('nilai_setoran');

        if ($academicYear?->start_date) {
            [$start, $end] = $this->semesterRange($academicYear, $semester);
            $query->whereBetween('setoran_date', [$start->toDateString(), $end->toDateString()]);
        }

        $average = $query->avg('nilai_setoran');

        return $average !== null ? round((float) $average, 2) : null;
    }

    public function scoreFor(
        string $studentId,
        TeacherAdminBook $book,
        string $academicYearId,
        string $semester,
        string $jenis,
        ?NilaiSumatif $row = null,
    ): ?float {
        $existing = AcademicNilai::resolveTampil($jenis, $row);

        if (! $this->isTahfidzSubject($book->subject)) {
            return $existing;
        }

        $setoran = $this->setoranAverage($studentId, $academicYearId, $semester);
        if ($setoran === null) {
            return $existing;
        }

        $weights = config('tahfidz.rapor_weights', []);
        $components = ['setoran' => [$setoran, (float) ($weights['setoran'] ?? 0.60)]];

        if ($jenis === 'sas') {
            if ($row?->sts !== null) {
                $components['sts'] = [(float) $row->sts, (float) ($weights['sts'] ?? 0.20)];
            }
            if ($row?->sas !== null) {
                $components['sas'] = [(float) $row->sas, (float) ($weights['sas'] ?? 0.20)];
            }
        } elseif ($row?->sts !== null) {
            $components['sts'] = [(float) $row->sts, (float) ($weights['sts'] ?? 0.20)];
        }

        $weightTotal = collect($components)->sum(fn ($component) => $component[1]);
        if ($weightTotal <= 0) {
            return $setoran;
        }

        $score = collect($components)->sum(fn ($component) => $component[0] * $component[1]) / $weightTotal;

        return round($score, 2);
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
