<?php

namespace App\Services\Arsip;

use App\Models\ArchiveDocument;
use App\Models\NilaiFormatif;
use App\Models\NilaiSumatif;
use App\Models\StudentClassHistory;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\SumatifHarianService;

/**
 * Snapshot buku nilai saat dikunci final.
 *
 * Menyimpan kondisi nilai pada momen penguncian (termasuk siswa yang sudah
 * pindah/naik kelas) sebagai dokumen arsip ber-hash, sehingga nilai final
 * tetap merepresentasikan kondisi saat diterbitkan meski konfigurasi berubah.
 */
class NilaiBookArchiver
{
    public function __construct(private readonly DocumentArchiveService $documents) {}

    public function snapshot(TeacherAdminBook $book, ?User $user): ArchiveDocument
    {
        $book->loadMissing(['subject', 'studyGroup.gradeLevel', 'academicYear', 'teacher:id,name']);

        $sumatifService = app(SumatifHarianService::class);
        $columns = $sumatifService->columnsFor($book);

        $histories = StudentClassHistory::with('student:id,name,nisn,nis')
            ->where('study_group_id', $book->study_group_id)
            ->where('academic_year_id', $book->academic_year_id)
            ->orderByDesc('is_active')
            ->orderBy('attendance_number')
            ->get();

        $sumatif = NilaiSumatif::where('admin_book_id', $book->id)
            ->where('semester', $book->semester)
            ->get()
            ->keyBy('student_id');

        $formatif = NilaiFormatif::where('admin_book_id', $book->id)
            ->where('semester', $book->semester)
            ->get()
            ->keyBy('student_id');

        $students = $histories->map(function (StudentClassHistory $history) use ($sumatif, $formatif, $sumatifService, $columns) {
            $row = $sumatif->get($history->student_id);
            $fmt = $formatif->get($history->student_id);

            $num = fn ($value) => $value === null || $value === '' ? null : (float) $value;

            return [
                'student_id' => $history->student_id,
                'nama' => $history->student?->name,
                'nisn' => $history->student?->nisn,
                'nis' => $history->student?->nis,
                'anggota_aktif' => (bool) $history->is_active,
                'nilai' => $sumatifService->valuesFor($row, $columns),
                'nilai_ujian' => [
                    'rs' => $num($row?->rs),
                    'sts' => $num($row?->sts),
                    'sts_murni' => $num($row?->sts_murni),
                    'raport_sts' => $num($row?->raport_sts),
                    'sas' => $num($row?->sas),
                    'sas_murni' => $num($row?->sas_murni),
                    'rsa' => $num($row?->rsa),
                    'nr_murni' => $num($row?->nr_murni),
                    'nr_final' => $num($row?->nr_final),
                    'ket' => $row?->ket,
                ],
                'formatif' => $fmt?->only(['skor_lkpd', 'skor_diskusi', 'skor_kuis', 'skor_antarteman', 'nr_final']),
            ];
        })->values()->all();

        $payload = [
            'jenis' => 'snapshot_nilai_buku',
            'digenerate_pada' => now()->toIso8601String(),
            'dikunci_pada' => $book->locked_at?->toIso8601String() ?? now()->toIso8601String(),
            'sekolah_id' => $book->school_id,
            'tahun_ajaran' => [
                'id' => $book->academic_year_id,
                'nama' => $book->academicYear?->name,
                'semester' => $book->semester,
            ],
            'rombel' => [
                'id' => $book->study_group_id,
                'nama' => $book->studyGroup?->name,
                'tingkat' => $book->studyGroup?->gradeLevel?->name,
            ],
            'mapel' => [
                'id' => $book->subject_id,
                'nama' => $book->subject?->name,
            ],
            'guru' => [
                'id' => $book->teacher_id,
                'nama' => $book->teacher?->name,
            ],
            'bobot_nilai' => [
                'rs' => (float) $book->nr_final_weight_rs,
                'sts' => (float) $book->nr_final_weight_sts,
                'sas' => (float) $book->nr_final_weight_sas,
            ],
            'kolom_sumatif' => $columns,
            'siswa' => $students,
        ];

        $contents = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $title = sprintf(
            'Snapshot Nilai %s — %s — Semester %s %s',
            $book->subject?->name ?? 'Mapel',
            $book->studyGroup?->name ?? 'Rombel',
            ucfirst((string) $book->semester),
            $book->academicYear?->name ?? ''
        );

        return $this->documents->store([
            'school_id' => $book->school_id,
            'academic_year_id' => $book->academic_year_id,
            'semester' => $book->semester,
            'type' => 'snapshot_nilai',
            'title' => trim($title),
            'record_type' => TeacherAdminBook::class,
            'record_id' => $book->id,
            'study_group_id' => $book->study_group_id,
            'generated_by' => $user?->id,
            'notes' => 'Dibuat otomatis saat buku nilai dikunci final.',
        ], $contents, 'json');
    }
}
