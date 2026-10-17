<?php

namespace App\Services\Arsip;

use App\Models\AcademicYear;
use App\Models\AdminPresensiHarian;
use App\Models\NilaiSumatif;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\TeacherAdminBook;
use App\Support\AcademicNilai;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Data & renderer Dokumen Leger — satu sumber untuk:
 *  - tampilan cetak Leger (NilaiKelasController::legerCetak), dan
 *  - arsip dokumen periode (ArsipkanLegerRombelJob).
 */
class LegerPdfService
{
    /**
     * @return array<string, mixed>
     */
    public function buildData(
        ?string $schoolId,
        StudyGroup $studyGroup,
        ?AcademicYear $selectedAy,
        string $selectedAyId,
        string $selectedSem,
        string $jenis
    ): array {
        $studyGroupId = $studyGroup->id;

        $subjectMap = TeacherAdminBook::with('subject')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('semester', $selectedSem)
            ->where('is_active', true)
            ->get()
            ->groupBy('subject_id')
            ->map(fn ($books, $subjectId) => $books->first())
            ->values()
            ->pluck('subject')
            ->filter()
            ->sortBy(fn ($s) => AcademicNilai::subjectSortOrder($s->name, $s->category ?? ''))
            ->values();

        $bookMap = $subjectMap->mapWithKeys(fn ($s) => [
            $s->id => TeacherAdminBook::with('subject', 'kktp')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('subject_id', $s->id)
                ->where('study_group_id', $studyGroupId)
                ->where('academic_year_id', $selectedAyId)
                ->where('semester', $selectedSem)
                ->where('is_active', true)
                ->first(),
        ]);

        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('is_active', true)
            ->orderBy('attendance_number')
            ->get();

        $nilaiMap = [];
        if ($students->isNotEmpty() && $selectedAyId) {
            $studentIds = $students->pluck('student_id')->toArray();
            $subjectIds = $subjectMap->pluck('id')->toArray();
            $nilaiRows = NilaiSumatif::whereIn(
                'admin_book_id',
                TeacherAdminBook::whereIn('subject_id', $subjectIds)
                    ->where('study_group_id', $studyGroupId)
                    ->where('academic_year_id', $selectedAyId)
                    ->where('semester', $selectedSem)
                    ->where('is_active', true)->pluck('id')
            )->whereIn('student_id', $studentIds)->where('semester', $selectedSem)->get();

            foreach ($nilaiRows as $n) {
                $nilaiMap[$n->student_id][$n->admin_book_id] = $n;
            }
        }

        $presensiMap = [];
        if ($selectedAyId) {
            $presensiRaw = AdminPresensiHarian::where('academic_year_id', $selectedAyId)
                ->where('study_group_id', $studyGroupId)
                ->where('semester', $selectedSem)->get()->groupBy('student_id');
            foreach ($presensiRaw as $sid => $rows) {
                $presensiMap[$sid] = [
                    's' => $rows->where('status', 'S')->count(),
                    'i' => $rows->where('status', 'I')->count(),
                    'a' => $rows->where('status', 'A')->count(),
                ];
            }
        }

        $nilaiResolved = [];
        foreach ($students as $history) {
            $sid = $history->student_id;
            foreach ($subjectMap as $subject) {
                $book = $bookMap[$subject->id] ?? null;
                if (! $book) {
                    continue;
                }
                $nilaiResolved[$sid][$book->id] = AcademicNilai::resolveTampil($jenis, $nilaiMap[$sid][$book->id] ?? null);
            }
        }

        $legerAggMap = [];
        foreach ($students as $history) {
            $sid = $history->student_id;
            $total = 0;
            $count = 0;
            foreach ($subjectMap as $subject) {
                $book = $bookMap[$subject->id] ?? null;
                if (! $book) {
                    continue;
                }
                $val = $nilaiResolved[$sid][$book->id] ?? null;
                if ($val !== null) {
                    $total += $val;
                    $count++;
                }
            }
            $legerAggMap[$sid] = $count > 0 ? $total / $count : null;
        }

        $rankMap = [];
        $sorted = collect($legerAggMap)->filter(fn ($v) => $v !== null)->sortDesc();
        $rank = 1;
        foreach ($sorted as $sid => $val) {
            $rankMap[$sid] = $rank++;
        }

        return [
            'studyGroup' => $studyGroup,
            'subjectMap' => $subjectMap,
            'bookMap' => $bookMap,
            'students' => $students,
            'nilaiMap' => $nilaiMap,
            'selectedAyId' => $selectedAyId,
            'selectedSem' => $selectedSem,
            'selectedAy' => $selectedAy,
            'presensiMap' => $presensiMap,
            'legerAggMap' => $legerAggMap,
            'rankMap' => $rankMap,
            'jenis' => $jenis,
            'nilaiResolved' => $nilaiResolved,
        ];
    }

    /**
     * PDF Leger (landscape) — bytes.
     */
    public function render(
        ?string $schoolId,
        StudyGroup $studyGroup,
        ?AcademicYear $selectedAy,
        string $selectedAyId,
        string $selectedSem,
        string $jenis
    ): string {
        $data = $this->buildData($schoolId, $studyGroup, $selectedAy, $selectedAyId, $selectedSem, $jenis);

        $html = view('nilai-kelas.leger-cetak', $data + ['userId' => null])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return (string) $dompdf->output();
    }
}
