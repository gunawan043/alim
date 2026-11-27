<?php

namespace App\Services\Arsip;

use App\Models\AcademicYear;
use App\Models\AdminPresensiHarian;
use App\Models\LegerPresensi;
use App\Models\NilaiSumatif;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\TahfidzJuzProgress;
use App\Models\TeacherAdminBook;
use App\Services\RaporSubjectOrderService;
use App\Services\TahfidzRaporService;
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
            ->values();

        // Urutan & daftar mapel mengikuti pengaturan TINGKAT di Peta Kurikulum
        // (Pondok → Dinas → nomor urut), sama dengan rapor.
        $orderService = app(RaporSubjectOrderService::class);
        $subjectMap = $orderService->sortSubjects($subjectMap, $studyGroup->grade_level_id, $selectedAyId);

        $reportSubjectIds = $orderService->reportSubjectIds($schoolId, $selectedAyId, $selectedSem, $studyGroup->grade_level_id);
        if ($reportSubjectIds !== null) {
            $subjectMap = $subjectMap
                ->filter(fn ($subject) => in_array($subject->id, $reportSubjectIds, true))
                ->values();
        }

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
            ->orderedByStudent()
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

        // Rekap presensi: S = hadir + terlambat, I = izin + sakit, A = alpa.
        // Isian manual di leger (admin_leger_presensi) menimpa hitungan ini.
        $presensiMap = [];
        if ($selectedAyId) {
            $presensiRaw = AdminPresensiHarian::where('academic_year_id', $selectedAyId)
                ->where('study_group_id', $studyGroupId)
                ->where('semester', $selectedSem)->get()->groupBy('student_id');

            foreach ($presensiRaw as $sid => $rows) {
                $presensiMap[$sid] = [
                    's' => $rows->filter(fn ($r) => in_array($r->status, ['hadir', 'terlambat'], true))->count(),
                    'i' => $rows->filter(fn ($r) => in_array($r->status, ['izin', 'sakit'], true))->count(),
                    'a' => $rows->where('status', 'alpa')->count(),
                ];
            }

            $manual = LegerPresensi::where('study_group_id', $studyGroupId)
                ->where('academic_year_id', $selectedAyId)
                ->where('semester', $selectedSem)
                ->get();

            foreach ($manual as $row) {
                $base = $presensiMap[$row->student_id] ?? ['s' => 0, 'i' => 0, 'a' => 0];
                $presensiMap[$row->student_id] = [
                    's' => $row->s ?? $base['s'],
                    'i' => $row->i ?? $base['i'],
                    'a' => $row->a ?? $base['a'],
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
                $nilaiResolved[$sid][$book->id] = app(TahfidzRaporService::class)
                    ->scoreFor($sid, $book, $selectedAyId, $selectedSem, $jenis, $nilaiMap[$sid][$book->id] ?? null);
            }
        }

        // Mapel tahfidz dicetak seperti mapel umum: satu kolom nilai (NR
        // Final, lewat $nilaiResolved). Rincian kolom hanya di tab Mapel.

        // Komponen STS/SAS per mapel (mis. Praktik & Tulis). Hanya mapel yang
        // punya komponen yang mendapat sub-kolom tambahan di leger.
        $komponenService = app(\App\Services\SumatifKomponenService::class);
        $componentColumns = [];
        $componentValues = [];

        foreach ($subjectMap as $subject) {
            $book = $bookMap[$subject->id] ?? null;
            if (! $book) {
                continue;
            }

            $stsCols = $komponenService->columnsFor($book, 'sts');
            $sasCols = $komponenService->columnsFor($book, 'sas');

            if ($stsCols === [] && $sasCols === []) {
                continue;
            }

            $componentColumns[$subject->id] = ['sts' => $stsCols, 'sas' => $sasCols];

            foreach ($students as $history) {
                $sid = $history->student_id;
                $row = $nilaiMap[$sid][$book->id] ?? null;
                $componentValues[$sid][$book->id] = [
                    'sts' => $komponenService->valuesFor($row, $stsCols, 'sts'),
                    'sas' => $komponenService->valuesFor($row, $sasCols, 'sas'),
                ];
            }
        }

        // Catatan rapor per predikat (dipakai bila catatan wali kosong).
        $predicateNotes = \App\Models\RaporPredicateNote::mapFor(
            (string) $studyGroup->id,
            (string) $selectedAyId,
            (string) $selectedSem,
        );
        $predicateNotesAr = \App\Models\RaporPredicateNote::mapForArabic(
            (string) $studyGroup->id,
            (string) $selectedAyId,
            (string) $selectedSem,
        );

        // Bobot per mapel (khusus SAS) dibaca dari TINGKAT (Peta Kurikulum):
        // satu bobot untuk semua rombel. Kosong = JP ÷ 2.
        $bobotMap = $orderService->bobotForGradeLevel(
            $studyGroup->grade_level_id,
            $selectedAyId,
            $subjectMap->pluck('id')->all(),
        );

        $legerAggMap = [];
        $legerJumlahMap = [];
        foreach ($students as $history) {
            $sid = $history->student_id;
            $total = 0;
            $count = 0;
            $bobotTerisi = 0.0;
            $tertimbang = 0.0;
            foreach ($subjectMap as $subject) {
                $book = $bookMap[$subject->id] ?? null;
                if (! $book) {
                    continue;
                }
                $val = $nilaiResolved[$sid][$book->id] ?? null;
                if ($val !== null) {
                    $total += $val;
                    $count++;
                    $bobot = (float) ($bobotMap[$subject->id] ?? 0);
                    $bobotTerisi += $bobot;
                    $tertimbang += $bobot * (float) $val;
                }
            }

            $legerJumlahMap[$sid] = $tertimbang;

            if ($jenis === 'sas' && $bobotTerisi > 0) {
                // Rata-rata SAS berbobot, sejalan dengan rapor: Σ(bobot×nilai) ÷ Σbobot.
                $legerAggMap[$sid] = round($tertimbang / $bobotTerisi, 1);
            } else {
                $legerAggMap[$sid] = $count > 0 ? $total / $count : null;
            }
        }

        $rankMap = [];
        $sorted = collect($legerAggMap)->filter(fn ($v) => $v !== null)->sortDesc();
        $rank = 1;
        foreach ($sorted as $sid => $val) {
            $rankMap[$sid] = $rank++;
        }

        $jumlahJuzMap = [];
        if ($students->isNotEmpty() && $selectedAyId) {
            $jumlahJuzMap = TahfidzJuzProgress::query()
                ->whereIn('student_id', $students->pluck('student_id'))
                ->where('academic_year_id', $selectedAyId)
                ->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])
                ->get()
                ->groupBy('student_id')
                ->map->count()
                ->all();
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
            'legerJumlahMap' => $legerJumlahMap,
            'bobotMap' => $bobotMap,
            'rankMap' => $rankMap,
            'jenis' => $jenis,
            'nilaiResolved' => $nilaiResolved,
            'jumlahJuzMap' => $jumlahJuzMap,
            'componentColumns' => $componentColumns,
            'componentValues' => $componentValues,
            'predicateNotes' => $predicateNotes,
            'predicateNotesAr' => $predicateNotesAr,
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
