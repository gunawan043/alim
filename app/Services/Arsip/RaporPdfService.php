<?php

namespace App\Services\Arsip;

use App\Models\AcademicYear;
use App\Models\AdminPresensiHarian;
use App\Models\NilaiSumatif;
use App\Models\RaporDistributionSetting;
use App\Models\RaportRegistration;
use App\Models\School;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\TeacherAdminBook;
use App\Services\ArabicName\ArabicNameResolver;
use App\Services\TahfidzRaporService;
use App\Support\AcademicNilai;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Renderer dokumen Rapor (PDF) — SATU sumber untuk:
 *  - cetak per-santri & cetak massal (NilaiKelasController), dan
 *  - arsip dokumen periode (ArsipkanRaporRombelJob).
 *
 * Perilaku identik dengan cetak manual: nilai final SAS disimpan ke
 * raport_registrations HANYA untuk TA aktif (TA arsip tidak pernah ditulis).
 */
class RaporPdfService
{
    public const BAHASA_ID = 'id';

    public const BAHASA_AR = 'ar';

    /**
     * Label dokumen per bahasa. Teks Arab di-shape saat dirender oleh view.
     *
     * @return array<string, string>
     */
    public function labels(string $bahasa): array
    {
        if ($bahasa === self::BAHASA_AR) {
            return [
                'title_sts' => 'تقرير نتائج التعلم لمنتصف الفصل الدراسي',
                'title_sas' => 'تقرير نتائج التعلم لنهاية الفصل الدراسي',
                'nama' => 'الاسم',
                'semester' => 'الفصل الدراسي',
                'kelas' => 'الفصل',
                'tahun_ajaran' => 'السنة الدراسية',
                'nis_nisn' => 'رقم الطالب / رقم الهوية',
                'no' => 'الرقم',
                'mapel' => 'المادة الدراسية',
                'kktp' => 'الحد الأدنى',
                'nilai' => 'الدرجة',
                'keterangan' => 'الملاحظة',
                'jumlah_nilai' => 'مجموع الدرجات',
                'predikat' => 'التقدير',
                'rata_rata' => 'المعدل',
                'catatan_kehadiran' => 'ملاحظات الحضور',
                'catatan_wali' => 'ملاحظات ولي الفصل',
                'sakit' => 'المرض',
                'izin' => 'الإذن',
                'alpa' => 'الغياب',
                'hari' => 'يوم',
                'orang_tua' => 'الوالد / الولي',
                'wali_kelas' => 'ولي الفصل',
                'mengetahui_kepala' => 'يعلم، مدير المدرسة',
                'nip' => 'رقم الموظف',
                'halaman' => 'الصفحة',
                'dari' => 'من',
                'tanggal' => '',
            ];
        }

        return [
            'title_sts' => 'Laporan Hasil Belajar Tengah Semester',
            'title_sas' => 'Laporan Hasil Belajar Akhir Semester',
            'nama' => 'Nama',
            'semester' => 'Semester',
            'kelas' => 'Kelas',
            'tahun_ajaran' => 'Tahun Ajaran',
            'nis_nisn' => 'NIS / NISN',
            'no' => 'No',
            'mapel' => 'Mata Pelajaran',
            'kktp' => 'KKTP',
            'nilai' => 'Nilai',
            'keterangan' => 'Keterangan',
            'jumlah_nilai' => 'Jumlah Nilai',
            'predikat' => 'Predikat',
            'rata_rata' => 'Nilai Rata-Rata',
            'catatan_kehadiran' => 'Catatan Kehadiran',
            'catatan_wali' => 'Catatan Wali Kelas',
            'sakit' => 'Sakit',
            'izin' => 'Izin',
            'alpa' => 'Alpa',
            'hari' => 'Hari',
            'orang_tua' => 'Orang Tua / Wali',
            'wali_kelas' => 'Wali Kelas',
            'mengetahui_kepala' => 'Mengetahui, Kepala Sekolah',
            'nip' => 'NIP.',
            'halaman' => 'Halaman',
            'dari' => 'dari',
            'tanggal' => '',
        ];
    }

    /**
     * Mapel + buku yang dipakai rapor, sudah difilter dan diurutkan
     * berdasarkan pengaturan mapel rapor.
     *
     * @param  array<int, string>|null  $reportSubjectIds
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    private function subjectBooks(
        ?string $schoolId,
        string $studyGroupId,
        string $selectedAyId,
        string $selectedSem,
        ?array $reportSubjectIds = null,
    ): array {
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

        if (! empty($reportSubjectIds)) {
            $order = array_flip($reportSubjectIds);
            $subjectMap = $subjectMap
                ->filter(fn ($subject) => isset($order[$subject->id]))
                ->sortBy(fn ($subject) => $order[$subject->id])
                ->values();
        }

        $bookMap = $subjectMap->mapWithKeys(fn ($s) => [
            $s->id => TeacherAdminBook::with('subject', 'kktp')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('subject_id', $s->id)
                ->where('study_group_id', $studyGroupId)
                ->where('academic_year_id', $selectedAyId)
                ->where('semester', $selectedSem)
                ->where('is_active', true)->first(),
        ]);

        return [$subjectMap, $bookMap];
    }

    /**
     * Semua mapel yang tersedia untuk rombel/TA/semester ini.
     *
     * @return \Illuminate\Support\Collection
     */
    public function availableSubjects(
        ?string $schoolId,
        StudyGroup $studyGroup,
        string $selectedAyId,
        string $selectedSem,
    ) {
        [$subjects] = $this->subjectBooks($schoolId, $studyGroup->id, $selectedAyId, $selectedSem);

        return $subjects;
    }

    /**
     * Ringkasan nilai/predikat/peringkat untuk tabel daftar santri.
     * Nilai dihitung dari mapel yang dipilih dengan jenis nilai akhir (SAS).
     *
     * @param  iterable<int, Student>  $students
     * @param  array<int, string>|null  $reportSubjectIds
     * @return array<string, array{nilai: float|null, predikat: string, rank: int|null}>
     */
    public function summariesForStudents(
        ?string $schoolId,
        StudyGroup $studyGroup,
        string $selectedAyId,
        string $selectedSem,
        string $jenis,
        iterable $students,
        ?array $reportSubjectIds = null,
    ): array {
        [$subjectMap, $bookMap] = $this->subjectBooks(
            $schoolId, $studyGroup->id, $selectedAyId, $selectedSem, $reportSubjectIds
        );

        $students = collect($students)->filter(fn ($s) => $s instanceof Student)->values();
        $studentIds = $students->pluck('id')->all();

        $nilaiRows = NilaiSumatif::query()
            ->whereIn('admin_book_id', $bookMap->pluck('id'))
            ->whereIn('student_id', $studentIds)
            ->where('semester', $selectedSem)
            ->get()
            ->groupBy('student_id');

        $tahfidz = app(TahfidzRaporService::class);
        $summaries = [];

        foreach ($students as $student) {
            $nilaiMap = $nilaiRows->get($student->id, collect())->keyBy('admin_book_id');
            $total = 0.0;
            $count = 0;

            foreach ($subjectMap as $subject) {
                $book = $bookMap[$subject->id] ?? null;
                if (! $book) {
                    continue;
                }

                $score = $tahfidz->scoreFor(
                    $student->id,
                    $book,
                    $selectedAyId,
                    $selectedSem,
                    $jenis,
                    $nilaiMap[$book->id] ?? null,
                );

                // Sebelum SAS diisi, tampilkan nilai STS sebagai ringkasan sementara
                // agar tabel data tidak kosong saat guru baru menyiapkan rapor.
                if ($score === null && $jenis === 'sas') {
                    $score = $tahfidz->scoreFor(
                        $student->id,
                        $book,
                        $selectedAyId,
                        $selectedSem,
                        'sts',
                        $nilaiMap[$book->id] ?? null,
                    );
                }

                if ($score !== null) {
                    $total += (float) $score;
                    $count++;
                }
            }

            $average = $count > 0 ? round($total / $count, 1) : null;
            $summaries[$student->id] = [
                'nilai' => $average,
                'predikat' => AcademicNilai::predikat($average),
                'rank' => null,
            ];
        }

        $rank = 1;
        foreach (collect($summaries)->filter(fn ($row) => $row['nilai'] !== null)->sortByDesc('nilai') as $studentId => $row) {
            $summaries[$studentId]['rank'] = $rank++;
        }

        return $summaries;
    }

    /**
     * @return array<string, mixed> peta studentId => data santri (format view rapor)
     */
    public function buildSantriData(
        ?string $schoolId,
        StudyGroup $studyGroup,
        ?AcademicYear $selectedAy,
        string $selectedAyId,
        string $selectedSem,
        string $jenis,
        Student $student,
        ?array $reportSubjectIds = null,
    ): array {
        $studentId = $student->id;
        $studyGroupId = $studyGroup->id;

        [$subjectMap, $bookMap] = $this->subjectBooks(
            $schoolId, $studyGroupId, $selectedAyId, $selectedSem, $reportSubjectIds
        );

        $nilaiMap = [];
        $studentNilaiRows = NilaiSumatif::whereIn('admin_book_id', $bookMap->pluck('id'))
            ->where('student_id', $studentId)
            ->where('semester', $selectedSem)
            ->get();

        foreach ($studentNilaiRows as $n) {
            $nilaiMap[$n->admin_book_id] = $n;
        }

        $presensiRaw = AdminPresensiHarian::where('academic_year_id', $selectedAyId)
            ->where('study_group_id', $studyGroupId)
            ->where('student_id', $studentId)
            ->where('semester', $selectedSem)->get();

        $sCount = $presensiRaw->where('status', 'S')->count();
        $iCount = $presensiRaw->where('status', 'I')->count();
        $aCount = $presensiRaw->where('status', 'A')->count();

        $mapelRows = [];
        $jumlahNilai = 0;
        $jumlahMapel = 0;

        foreach ($subjectMap as $idx => $subject) {
            $book = $bookMap[$subject->id] ?? null;
            $n = $book ? ($nilaiMap[$book->id] ?? null) : null;
            $nilai = $book
                ? app(TahfidzRaporService::class)->scoreFor($studentId, $book, $selectedAyId, $selectedSem, $jenis, $n)
                : AcademicNilai::resolveTampil($jenis, $n);

            if ($nilai !== null) {
                $jumlahNilai += $nilai;
                $jumlahMapel++;
            }

            $mapelRows[] = [
                'no' => $idx + 1,
                'mapel' => $subject->name,
                'mapel_ar' => app(ArabicNameResolver::class)->subjectName($subject),
                'kkm' => AcademicNilai::kkmLabel($book),
                'nilai' => $nilai !== null ? number_format($nilai, 1) : '',
                'keterangan' => AcademicNilai::keterangan($nilai, $book),
            ];
        }

        $rata = $jumlahMapel > 0 ? round($jumlahNilai / $jumlahMapel, 1) : null;
        $predikat = AcademicNilai::predikat($rata);

        $registration = RaportRegistration::where('student_id', $studentId)
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('semester', $selectedSem)
            ->first();

        $catatanWali = $registration?->homeroom_note;

        // Final score & predikat hanya disimpan untuk dokumen SAS (nilai akhir) pada TA aktif
        // — cetak/arsip TA arsip tidak menulis apa pun ke data periode lama.
        if ($jenis === 'sas') {
            $activeYearId = AcademicYear::where('is_active', true)->value('id');
            $isArchivedAy = $activeYearId && (string) $selectedAyId !== (string) $activeYearId;

            if (! $isArchivedAy) {
                RaportRegistration::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'study_group_id' => $studyGroupId,
                        'academic_year_id' => $selectedAyId,
                        'semester' => $selectedSem,
                    ],
                    [
                        'final_score' => $rata,
                        'predicate' => $predikat,
                    ]
                );
            }
        }

        return [
            $studentId => [
                'Nama' => $student->name,
                'NamaAr' => app(ArabicNameResolver::class)->studentName($student),
                'NIS' => $student->nis ?? '-',
                'NISN' => $student->nisn ?? '-',
                'Kelas' => $studyGroup->name,
                'Semester' => ucfirst($selectedSem),
                'TahunAjaran' => $selectedAy?->name ?? '-',
                'Jenis' => $jenis,
                'Mapel' => $mapelRows,
                'Jumlah' => $jumlahMapel > 0 ? number_format($jumlahNilai, 1) : '-',
                'Rata' => $rata !== null ? number_format($rata, 1) : '-',
                'Predikat' => $predikat,
                'Sakit' => $sCount,
                'Izin' => $iCount,
                'Alpa' => $aCount,
                'CatatanWali' => $catatanWali,
            ],
        ];
    }

    /**
     * HTML rapor satu santri (dipakai untuk PDF & pengujian konten).
     */
    public function renderHtml(
        ?string $schoolId,
        StudyGroup $studyGroup,
        ?AcademicYear $selectedAy,
        string $selectedAyId,
        string $selectedSem,
        string $jenis,
        Student $student,
        string $bahasa = self::BAHASA_ID,
        ?array $layout = null,
    ): string {
        $bahasa = $bahasa === self::BAHASA_AR ? self::BAHASA_AR : self::BAHASA_ID;

        $setting = RaporDistributionSetting::forContext($studyGroup->school_id, $selectedAyId, $selectedSem);
        $santri = $this->buildSantriData($schoolId, $studyGroup, $selectedAy, $selectedAyId, $selectedSem, $jenis, $student, $setting?->reportSubjectIds());
        $layout = $layout ?? ($setting?->layoutSettings() ?? RaporDistributionSetting::defaultLayout());

        $resolver = app(ArabicNameResolver::class);

        return view('nilai-kelas.rapor-cetak', [
            'santri' => $santri,
            'studyGroup' => $studyGroup,
            'kopBase64' => $this->schoolKopBase64($studyGroup->school),
            'bahasa' => $bahasa,
            'labels' => $this->labels($bahasa),
            'distributionDate' => $setting?->distribution_date,
            'distributionCity' => $setting?->city,
            'layout' => $layout,
            'waliNameAr' => $resolver->userName($studyGroup->homeroomTeacher),
            'kspNameAr' => $resolver->principalName($studyGroup->school),
        ])->render();
    }

    /**
     * PDF rapor satu santri (bytes).
     */
    public function renderSingle(
        ?string $schoolId,
        StudyGroup $studyGroup,
        ?AcademicYear $selectedAy,
        string $selectedAyId,
        string $selectedSem,
        string $jenis,
        Student $student,
        string $bahasa = self::BAHASA_ID,
    ): string {
        $setting = RaporDistributionSetting::forContext($studyGroup->school_id, $selectedAyId, $selectedSem);
        $layout = $setting?->layoutSettings() ?? RaporDistributionSetting::defaultLayout();

        return $this->renderPdf($this->renderHtml(
            $schoolId, $studyGroup, $selectedAy, $selectedAyId, $selectedSem, $jenis, $student, $bahasa, $layout
        ), (string) $layout['paper_size']);
    }

    /**
     * PDF rapor massal (satu berkas, satu halaman per santri).
     *
     * @param  iterable<int, Student>  $students
     */
    public function renderMassal(
        ?string $schoolId,
        StudyGroup $studyGroup,
        ?AcademicYear $selectedAy,
        string $selectedAyId,
        string $selectedSem,
        string $jenis,
        iterable $students,
        string $bahasa = self::BAHASA_ID,
    ): string {
        $bahasa = $bahasa === self::BAHASA_AR ? self::BAHASA_AR : self::BAHASA_ID;

        $setting = RaporDistributionSetting::forContext($studyGroup->school_id, $selectedAyId, $selectedSem);
        $santri = [];

        foreach ($students as $student) {
            if (! $student instanceof Student) {
                continue;
            }

            $santri += $this->buildSantriData($schoolId, $studyGroup, $selectedAy, $selectedAyId, $selectedSem, $jenis, $student, $setting?->reportSubjectIds());
        }

        $layout = $setting?->layoutSettings() ?? RaporDistributionSetting::defaultLayout();

        $resolver = app(ArabicNameResolver::class);

        $html = view('nilai-kelas.rapor-cetak', [
            'santri' => $santri,
            'studyGroup' => $studyGroup,
            'kopBase64' => $this->schoolKopBase64($studyGroup->school),
            'bahasa' => $bahasa,
            'labels' => $this->labels($bahasa),
            'distributionDate' => $setting?->distribution_date,
            'distributionCity' => $setting?->city,
            'layout' => $layout,
            'waliNameAr' => $resolver->userName($studyGroup->homeroomTeacher),
            'kspNameAr' => $resolver->principalName($studyGroup->school),
        ])->render();

        return $this->renderPdf($html, (string) $layout['paper_size']);
    }

    public function renderPdf(string $html, string $paperSize = 'a4'): string
    {
        $paper = in_array(strtolower($paperSize), ['a4', 'folio', 'letter'], true)
            ? strtolower($paperSize)
            : 'a4';

        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    /**
     * Kop surat sekolah (base64) untuk dokumen PDF.
     */
    public function schoolKopBase64(?School $school): ?string
    {
        if (! $school || ! $school->kop_path) {
            return null;
        }

        $kopAbsolute = storage_path('app/public/'.$school->kop_path);
        if (! file_exists($kopAbsolute)) {
            return null;
        }

        $ext = strtolower(pathinfo($kopAbsolute, PATHINFO_EXTENSION) ?: 'png');
        $mime = in_array($ext, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($kopAbsolute));
    }
}
