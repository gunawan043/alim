<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\SubjectKktp;
use App\Models\TeachingAssignment;
use App\Models\TujuanPembelajaran;
use App\Services\AcademicPeriodService;
use App\Services\CurriculumProfileService;
use App\Services\KurikulumAccess;
use App\Services\PekanEfektifService;
use App\Services\RaporSubjectOrderService;
use App\Services\SubjectHoursSynchronizer;
use App\Services\SubjectKktpService;
use App\Services\TeachingHoursResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Hub Kurikulum — menghubungkan tahun ajaran, satuan pendidikan, jenjang,
 * fase, mapel, guru, rombel, beban JP existing, CP, TP, dan ATP.
 *
 * Tidak menyimpan data baru: seluruh angka dibaca dari sumber yang sudah ada
 * (study_groups, grade_level_subjects, teaching_assignments, tujuan_pembelajaran,
 * capaian_pembelajaran, alur_tujuan_pembelajaran) + Pekan Efektif dari Kalender.
 */
class KurikulumController extends Controller
{
    public function __construct(
        private readonly PekanEfektifService $pekanService,
        private readonly TeachingHoursResolver $hoursResolver,
    ) {}

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('created_at')->get();

        // Default TA: TA yang punya penugasan (data operasional) → TA aktif → terbaru.
        $ayWithAssignments = TeachingAssignment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'active')
            ->selectRaw('academic_year_id, COUNT(*) as total')
            ->groupBy('academic_year_id')
            ->orderByDesc('total')
            ->pluck('academic_year_id')
            ->first();

        // Default TA: TA AKTIF selalu menjadi default. TA dengan penugasan
        // hanya dipakai sebagai fallback bila tidak ada TA aktif.
        $activeAy = $academicYears->firstWhere('is_active', true)
            ?? ($ayWithAssignments ? $academicYears->firstWhere('id', $ayWithAssignments) : null)
            ?? $academicYears->first();

        $academicYearId = $request->input('academic_year_id', $activeAy?->id);
        $academicYear = $academicYears->firstWhere('id', $academicYearId);
        $activeAcademicYearId = $academicYears->firstWhere('is_active', true)?->id;
        $isArchive = $academicYearId && $activeAcademicYearId
            && (string) $academicYearId !== (string) $activeAcademicYearId;

        $semester = $request->input('semester', $academicYear?->semester === 'genap' ? 'genap' : 'ganjil');
        if (! in_array($semester, ['ganjil', 'genap'], true)) {
            $semester = 'ganjil';
        }

        // Minggu efektif dari Kalender → Pekan Efektif (satu sumber data).
        $ringkasan = ($schoolId && $academicYearId)
            ? $this->pekanService->summary($schoolId, $academicYearId, $semester === 'ganjil' ? 1 : 2)
            : null;
        $mingguEfektif = (int) ($ringkasan['minggu_efektif'] ?? 0);

        $rows = collect();

        // Mapel + tingkat yang benar-benar diampu guru pada periode ini.
        // Null pada authorablePairs berarti editor/tim kurikulum (boleh menyusun semua mapel).
        $access = app(KurikulumAccess::class);
        $teachingAssignments = TeachingAssignment::query()
            ->where('teacher_id', $request->user()->id)
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->with('studyGroup:id,grade_level_id')
            ->get();

        $taughtSubjectIds = $teachingAssignments
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values();

        $taughtPairs = $teachingAssignments
            ->map(fn ($assignment) => $assignment->subject_id.'|'.$assignment->studyGroup?->grade_level_id)
            ->filter()
            ->unique()
            ->values();

        $authorablePairs = $access->isKurikulumEditor($request->user())
            ? null
            : $taughtPairs->all();

        $mapelFilter = $request->input('mapel') === 'diampu' ? 'diampu' : 'all';

        // Filter per tingkat (Kelas 1, 2, 3, …) — "Semua Tingkat" bila kosong.
        $gradeLevelId = $request->input('grade_level_id');
        $gradeLevelOptions = GradeLevel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('level')
            ->get(['id', 'name']);

        if ($academicYearId) {
            $gradeLevels = GradeLevel::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($gradeLevelId, fn ($q) => $q->where('id', $gradeLevelId))
                ->orderBy('level')
                ->get();

            $gradeLevelIds = $gradeLevels->pluck('id');

            $rombelByGrade = StudyGroup::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('academic_year_id', $academicYearId)
                ->get(['grade_level_id'])
                ->groupBy('grade_level_id')
                ->map->count();

            $gradeSubjects = GradeLevelSubject::with('subject')
                ->whereIn('grade_level_id', $gradeLevelIds)
                ->forAcademicYear($academicYearId)
                ->when($mapelFilter === 'diampu', fn ($q) => $q->whereIn('subject_id', $taughtSubjectIds->all() ?: ['-']))
                ->orderByAcademicYearFirst()
                ->get()
                ->groupBy('grade_level_id')
                ->map(function ($rows, $gradeLevelId) use ($mapelFilter, $taughtPairs) {
                    $rows = $rows
                        ->unique(fn ($r) => $r->subject_id.'|'.($r->track ?? ''))
                        ->filter(fn ($r) => $r->is_active);

                    if ($mapelFilter === 'diampu') {
                        $rows = $rows->filter(fn ($r) => $taughtPairs->contains(
                            $r->subject_id.'|'.$gradeLevelId
                        ));
                    }

                    return $rows->values();
                });

            // Peta Kurikulum menampilkan SELURUH struktur jenjang dalam cakupan satuan pendidikan
            // (termasuk mapel yang tidak diampu guru) agar GTK memahami keseluruhan kurikulum.
            // Hak edit TETAP dibatasi: KKTP via $canEditKktp + guard saveKktp;
            // CP/TP/ATP/Perangkat via KurikulumAccess::canManageSubject().

            $subjectIds = $gradeSubjects->flatten()->pluck('subject_id')->filter()->unique()->values()->all();

            // Jumlah guru pengampu per mapel PER SATUAN PENDIDIKAN (tidak menghitung lintas lembaga).
            $teachersBySubjectSchool = TeachingAssignment::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('academic_year_id', $academicYearId)
                ->where('status', 'active')
                ->whereIn('subject_id', $subjectIds)
                ->get(['subject_id', 'school_id', 'teacher_id'])
                ->groupBy(fn ($a) => $a->subject_id.'|'.$a->school_id)
                ->map(fn ($items) => $items->pluck('teacher_id')->unique()->count());

            $cpCounts = CapaianPembelajaran::query()
                ->active()
                ->forSchool($schoolId)
                ->whereIn('subject_id', $subjectIds)
                ->forAcademicYear($academicYearId)
                ->orderByAcademicYearFirst()
                ->get(['subject_id', 'fase', 'elemen', 'academic_year_id'])
                ->unique(fn ($cp) => $cp->subject_id.'|'.$cp->fase.'|'.($cp->elemen ?? ''))
                ->groupBy(fn ($cp) => $cp->subject_id.'|'.$cp->fase)
                ->map->count();

            $tpCounts = TujuanPembelajaran::query()
                ->active()
                ->bySchool($schoolId)
                ->byAcademicYear($academicYearId)
                ->bySemester($semester)
                ->whereIn('subject_id', $subjectIds)
                ->get(['subject_id'])
                ->groupBy('subject_id')
                ->map->count();

            $atpBy = AlurTujuanPembelajaran::query()
                ->bySchool($schoolId)
                ->byAcademicYear($academicYearId)
                ->bySemester($semester)
                ->get()
                ->keyBy(fn ($atp) => $atp->subject_id.'|'.$atp->grade_level_id);

            foreach ($gradeLevels as $grade) {
                $fase = $grade->fase;

                foreach ($gradeSubjects->get($grade->id, collect()) as $gradeSubject) {
                    $subject = $gradeSubject->subject;
                    if (! $subject) {
                        continue;
                    }

                    $weeklyHours = $this->hoursResolver->resolve(null, $subject, null, $grade->id, $academicYearId);
                    $atp = $atpBy->get($subject->id.'|'.$grade->id);

                    $rows->push([
                        'grade_level_id' => $grade->id,
                        'grade' => $grade->name,
                        'fase' => $fase,
                        'subject_id' => $subject->id,
                        'subject' => $subject->name,
                        'weekly_hours' => $weeklyHours,
                        'jp_efektif' => $weeklyHours * $mingguEfektif,
                        'rombel' => (int) ($rombelByGrade[$grade->id] ?? 0),
                        'guru' => (int) ($teachersBySubjectSchool[$subject->id.'|'.$grade->school_id] ?? 0),
                        'cp_count' => $fase ? (int) ($cpCounts[$subject->id.'|'.$fase] ?? 0) : 0,
                        'tp_count' => (int) ($tpCounts[$subject->id] ?? 0),
                        'atp_id' => $atp?->id,
                        'atp_total_jp' => $atp?->total_jp,
                        'atp_status' => $atp?->status,
                        'can_author_atp' => $authorablePairs === null
                            || in_array($subject->id.'|'.$grade->id, $authorablePairs, true),
                        // Pengaturan rapor per tingkat: asal mapel & nomor urut.
                        'subject_source' => $gradeSubject->subject_source,
                        'sort_order' => $gradeSubject->sort_order,
                        'is_rapor' => null,
                    ]);
                }
            }
        }

        // ── KKTP per (mapel × tingkat) untuk TA/semester terpilih ──
        $kktpMap = SubjectKktp::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->get(['subject_id', 'grade_level_id', 'kktp_score', 'kkm_score'])
            ->keyBy(fn ($k) => $k->subject_id.'|'.$k->grade_level_id);

        $rows = $rows->map(function ($row) use ($kktpMap) {
            $kktp = $kktpMap->get($row['subject_id'].'|'.$row['grade_level_id']);
            // kkm_score adalah nilai otoritatif; kktp_score adalah mirror lama.
            $row['kktp'] = $kktp?->kktp_score ?? $kktp?->kkm_score;

            return $row;
        });

        // ── Daftar mapel yang dicetak di rapor per TINGKAT ──
        // Belum pernah diatur → semua mapel aktif dianggap masuk rapor.
        $raporOrder = app(RaporSubjectOrderService::class);
        $raporSelected = [];
        foreach ($gradeLevelOptions as $grade) {
            $selected = $raporOrder->reportSubjectIds($schoolId, $academicYearId, $semester, $grade->id);
            $raporSelected[$grade->id] = $selected;
        }

        $rows = $rows->map(function ($row) use ($raporSelected) {
            $selected = $raporSelected[$row['grade_level_id']] ?? null;
            $row['is_rapor'] = $selected === null || in_array($row['subject_id'], $selected, true);

            return $row;
        });

        // Urutkan baris sesuai susunan rapor (blok Pondok → Pondok + Dinas →
        // Dinas, lalu nomor urut), per tingkat — sama persis dengan cetakan.
        $raporOrder = app(RaporSubjectOrderService::class);
        $rows = $rows
            ->groupBy('grade_level_id')
            ->flatMap(fn ($gradeRows, $rowGradeId) => $raporOrder->sortSubjects(
                Subject::query()->whereIn('id', $gradeRows->pluck('subject_id'))->get(),
                (string) $rowGradeId,
                $academicYearId,
            )->map(fn (Subject $subject) => $gradeRows->firstWhere('subject_id', $subject->id))->filter()->values())
            ->values();

        // Izin atur KKTP: tim kurikulum atau koordinator mapel.
        $isKoorMapelUser = (bool) optional($request->user()?->tugasTambahan)
            ->pluck('nama')
            ->contains(fn ($n) => str_contains(mb_strtolower((string) $n), 'koordinator guru'));
        $canEditKktp = $access->isKurikulumTeam($request->user()) || $isKoorMapelUser;

        // JP adalah struktur operasional, bukan dokumen guru: tim/koordinator
        // kurikulum DAN Kepala/Wakil Satuan Pendidikan boleh mengubahnya.
        $canEditJp = $access->canManageSubjectHours($request->user()) && ! $isArchive;

        // Susunan rapor (urutan, Pondok/Dinas, mapel yang dicetak) disimpan pada
        // Peta dan berlaku untuk semua rombel di tingkat itu. Tim kurikulum —
        // termasuk Wakil Kepala yang menjadi pengawas kurikulum — yang
        // menetapkannya, dan hanya pada TA yang belum dikunci.
        $canEditRapor = $access->isKurikulumTeam($request->user()) && ! $isArchive;

        // ── Total JP per tingkat (struktur periode terpilih, bukan baris tampil) ──
        $jpTotals = GradeLevelSubject::query()
            ->when($schoolId, fn ($q) => $q->whereHas('gradeLevel', fn ($g) => $g->where('school_id', $schoolId)))
            ->forAcademicYear($academicYearId)
            ->orderByAcademicYearFirst()
            ->get(['grade_level_id', 'subject_id', 'track', 'allocation_hours', 'academic_year_id', 'is_active'])
            ->groupBy('grade_level_id')
            ->map(fn ($items) => $items
                ->unique(fn ($r) => $r->subject_id.'|'.($r->track ?? ''))
                ->filter(fn ($r) => $r->is_active)
                ->sum('allocation_hours'));

        $jpSummary = $gradeLevelOptions->map(fn ($g) => [
            'name' => $g->name,
            'total' => (float) ($jpTotals[$g->id] ?? 0),
        ])->values();

        // Izin menyiapkan Peta TA baru: editor kurikulum (tim/koordinator kurikulum, super admin).
        $canPrepare = $access->isKurikulumEditor($request->user());
        $canManageProfile = app(KurikulumAccess::class)->isKurikulumTeam($request->user());

        $curriculumUpdates = (array) config('curriculum_updates.items', []);
        $curriculumUpdatesAt = (string) config('curriculum_updates.updated_at', '');

        $profileService = app(CurriculumProfileService::class);
        $curriculumProfile = $profileService->resolve($schoolId, $academicYearId);
        $curriculumLabels = $profileService->labels($curriculumProfile);

        return view('kurikulum.index', compact(
            'userId',
            'academicYears',
            'academicYearId',
            'semester',
            'mingguEfektif',
            'ringkasan',
            'rows',
            'gradeLevelOptions',
            'gradeLevelId',
            'canEditKktp',
            'canEditJp',
            'canEditRapor',
            'canPrepare',
            'taughtSubjectIds',
            'taughtPairs',
            'mapelFilter',
            'canManageProfile',
            'curriculumUpdates',
            'curriculumUpdatesAt',
            'isArchive',
            'activeAcademicYearId',
            'jpSummary',
            'curriculumProfile',
            'curriculumLabels'
        ));
    }

    /**
     * ARSIP PETA KURIKULUM: siapkan Peta Kurikulum tahun ajaran tujuan dengan
     * menyalin struktur (mapel × tingkat + JP) dan KKTP dari TA sumber.
     * Idempoten — baris yang sudah ada pada TA tujuan tidak ditimpa, dan
     * TA sumber tidak pernah diubah sehingga arsip lama tetap utuh.
     */
    public function siapkan(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = $request->user();

        abort_unless(
            app(KurikulumAccess::class)->isKurikulumEditor($user),
            403,
            'Hanya tim kurikulum yang dapat menyiapkan Peta Kurikulum tahun ajaran baru.'
        );

        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'source_academic_year_id' => 'required|exists:academic_years,id|different:academic_year_id',
        ]);

        app(AcademicPeriodService::class)->assertNotLocked($data['academic_year_id'], 'Peta Kurikulum');

        $targetAyId = $data['academic_year_id'];
        $sourceAyId = $data['source_academic_year_id'];

        $createdStructure = 0;
        $createdKktp = 0;
        $createdCp = 0;

        DB::transaction(function () use ($schoolId, $targetAyId, $sourceAyId, $user, &$createdStructure, &$createdKktp, &$createdCp) {
            $sourceRows = GradeLevelSubject::query()
                ->when($schoolId, fn ($q) => $q->whereHas('gradeLevel', fn ($g) => $g->where('school_id', $schoolId)))
                ->where('is_active', true)
                ->forAcademicYear($sourceAyId)
                ->orderByAcademicYearFirst()
                ->get()
                ->unique(fn ($row) => $row->structureKey());

            foreach ($sourceRows as $row) {
                $exists = GradeLevelSubject::query()
                    ->where('grade_level_id', $row->grade_level_id)
                    ->where('subject_id', $row->subject_id)
                    ->when($row->track === null, fn ($q) => $q->whereNull('track'), fn ($q) => $q->where('track', $row->track))
                    ->where('academic_year_id', $targetAyId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                GradeLevelSubject::create([
                    'academic_year_id' => $targetAyId,
                    'grade_level_id' => $row->grade_level_id,
                    'subject_id' => $row->subject_id,
                    'allocation_hours' => $row->allocation_hours,
                    'track' => $row->track,
                    'is_active' => true,
                ]);

                $createdStructure++;
            }

            $kktpRows = SubjectKktp::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('academic_year_id', $sourceAyId)
                ->get();

            foreach ($kktpRows as $kktp) {
                $exists = SubjectKktp::query()
                    ->where('subject_id', $kktp->subject_id)
                    ->where('grade_level_id', $kktp->grade_level_id)
                    ->where('academic_year_id', $targetAyId)
                    ->where('semester', $kktp->semester)
                    ->exists();

                if ($exists) {
                    continue;
                }

                SubjectKktp::create([
                    'subject_id' => $kktp->subject_id,
                    'school_id' => $kktp->school_id,
                    'grade_level_id' => $kktp->grade_level_id,
                    'academic_year_id' => $targetAyId,
                    'semester' => $kktp->semester,
                    'kktp_score' => $kktp->kktp_score,
                    'kkm_score' => $kktp->kkm_score,
                    'notes' => $kktp->notes,
                    'created_by' => $user?->id,
                ]);

                $createdKktp++;
            }

            // CP juga ber-versi periode: salin CP efektif TA sumber ke TA tujuan.
            $cpRows = CapaianPembelajaran::query()
                ->active()
                ->forSchool($schoolId)
                ->forAcademicYear($sourceAyId)
                ->orderByAcademicYearFirst()
                ->get()
                ->unique(fn ($cp) => $cp->subject_id.'|'.$cp->fase.'|'.($cp->elemen ?? ''));

            foreach ($cpRows as $cp) {
                $exists = CapaianPembelajaran::query()
                    ->where('subject_id', $cp->subject_id)
                    ->where('fase', $cp->fase)
                    ->when($cp->elemen === null, fn ($q) => $q->whereNull('elemen'), fn ($q) => $q->where('elemen', $cp->elemen))
                    ->where('academic_year_id', $targetAyId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                CapaianPembelajaran::create([
                    'school_id' => $cp->school_id,
                    'subject_id' => $cp->subject_id,
                    'academic_year_id' => $targetAyId,
                    'fase' => $cp->fase,
                    'elemen' => $cp->elemen,
                    'deskripsi' => $cp->deskripsi,
                    'urutan' => $cp->urutan,
                    'is_active' => true,
                    'created_by' => $user?->id,
                ]);

                $createdCp++;
            }
        });

        $targetAy = AcademicYear::find($targetAyId);

        return back()->with(
            'success',
            "Peta Kurikulum {$targetAy?->name} disiapkan: {$createdStructure} baris struktur, {$createdKktp} KKTP, dan {$createdCp} CP disalin (TA sumber tidak diubah)."
        );
    }

    /**
     * Simpan JP per (mapel × tingkat) langsung dari Peta Kurikulum.
     *
     * Ini pintu utama pengaturan JP: cukup satu mapel dengan JP berbeda per
     * tingkat (mis. Bahasa Indonesia 4 JP di kelas 7, 3 JP di kelas 8) tanpa
     * perlu menggandakan mapel.
     *
     * Setiap nilai disebarkan lewat SubjectHoursSynchronizer sehingga JP
     * rombel dan pembagian tugas guru ikut menyesuaikan.
     *
     * Format: jp[grade_level_id][subject_id] = jumlah JP per minggu.
     */
    public function saveJp(Request $request, string $userId)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'jp' => 'required|array',
            'jp.*' => 'array',
            'jp.*.*' => 'nullable|integer|min:0|max:40',
        ]);

        $pendidikan = $this->simpanJp(
            $request,
            $request->attributes->get('schoolContextId'),
            $request->user(),
            $data['academic_year_id'],
            $data['jp'],
        );

        $pesan = "JP tersimpan ({$pendidikan['diubah']} mapel-tingkat).";
        if ($pendidikan['rombel'] > 0 || $pendidikan['guru'] > 0) {
            $pesan .= " Tersinkron ke {$pendidikan['rombel']} rombel dan {$pendidikan['guru']} penugasan guru.";
        }

        return back()->with('success', $pesan);
    }

    /**
     * Simpan KKTP (Kriteria Ketercapaian) langsung dari Peta Kurikulum.
     * Format: kktp[grade_level_id][subject_id] = nilai 0-100 (kosong = hapus).
     */
    public function saveKktp(Request $request, string $userId)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'kktp' => 'nullable|array',
            'kktp.*' => 'nullable|array',
            'kktp.*.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $hasil = $this->simpanKktp(
            $request,
            $request->attributes->get('schoolContextId'),
            $request->user(),
            $data['academic_year_id'],
            $data['semester'],
            $data['kktp'] ?? [],
        );

        $pesan = "KKTP tersimpan ({$hasil['saved']} mapel-tingkat) dan berlaku untuk semua rombel di tingkat tersebut.";
        if ($hasil['books'] > 0) {
            $pesan .= " {$hasil['books']} buku nilai diselaraskan.";
        }

        return back()->with('success', $pesan);
    }

    /**
     * Simpan pengaturan rapor per tingkat dari Peta Kurikulum:
     * urutan cetak, asal mapel, dan mapel mana yang masuk rapor.
     *
     * Format:
     *   struktur[grade_level_id][subject_id][urutan] = nomor urut
     *   struktur[grade_level_id][subject_id][sumber] = pondok|pondok_dinas|dinas
     *   struktur[grade_level_id][subject_id][rapor]  = 1|0
     */
    public function saveStruktur(Request $request, string $userId)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'struktur' => 'nullable|array',
            'struktur.*' => 'array',
            'struktur.*.*' => 'array',
            'struktur.*.*.urutan' => 'nullable|integer|min:-99|max:999',
            'struktur.*.*.sumber' => 'nullable|in:'.implode(',', array_keys(GradeLevelSubject::SOURCE_OPTIONS)),
            'struktur.*.*.rapor' => 'nullable|in:0,1',
        ]);

        $diubah = $this->simpanStruktur(
            $request,
            $request->attributes->get('schoolContextId'),
            $request->user(),
            $data['academic_year_id'],
            $data['semester'],
            $data['struktur'] ?? [],
        );

        return back()->with(
            'success',
            "Pengaturan rapor tersimpan ({$diubah} mapel-tingkat). Rapor semua rombel pada tingkat itu mengikuti urutan ini."
        );
    }

    /**
     * Simpan SELURUH pengaturan Peta Kurikulum sekaligus: JP, urutan, asal
     * mapel (Pondok/Dinas), dan N-KKTP — satu tombol untuk semua kolom.
     *
     * Setiap bagian hanya diproses bila pengguna berwenang atasnya, sehingga
     * Wakil Kepala tanpa hak KKTP tetap bisa menyimpan JP dan susunan rapor.
     */
    public function savePeta(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = $request->user();

        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'jp' => 'nullable|array',
            'jp.*' => 'array',
            'jp.*.*' => 'nullable|integer|min:0|max:40',
            'struktur' => 'nullable|array',
            'struktur.*' => 'array',
            'struktur.*.*' => 'array',
            'struktur.*.*.urutan' => 'nullable|integer|min:-99|max:999',
            'struktur.*.*.sumber' => 'nullable|in:'.implode(',', array_keys(GradeLevelSubject::SOURCE_OPTIONS)),
            'struktur.*.*.rapor' => 'nullable|in:0,1',
            'kktp' => 'nullable|array',
            'kktp.*' => 'nullable|array',
            'kktp.*.*' => 'nullable|numeric|min:0|max:100',
        ]);

        app(AcademicPeriodService::class)->assertNotLocked($data['academic_year_id'], 'Peta Kurikulum');

        $pesan = [];

        if (($data['jp'] ?? []) !== []) {
            $jp = $this->simpanJp($request, $schoolId, $user, $data['academic_year_id'], $data['jp']);
            $pesan[] = "JP {$jp['diubah']} mapel-tingkat";
        }

        if (($data['struktur'] ?? []) !== []) {
            $pesan[] = 'susunan rapor '.$this->simpanStruktur(
                $request,
                $schoolId,
                $user,
                $data['academic_year_id'],
                $data['semester'],
                $data['struktur'],
            ).' mapel-tingkat';
        }

        if (($data['kktp'] ?? []) !== []) {
            $kktp = $this->simpanKktp(
                $request,
                $schoolId,
                $user,
                $data['academic_year_id'],
                $data['semester'],
                $data['kktp'],
            );
            $pesan[] = "N-KKTP {$kktp['saved']} mapel-tingkat";
        }

        return back()->with(
            'success',
            $pesan === []
                ? 'Tidak ada perubahan untuk disimpan.'
                : 'Peta Kurikulum tersimpan: '.implode(', ', $pesan).'.'
        );
    }

    /**
     * Naikkan/turunkan satu mapel dalam susunan rapor satu tingkat.
     *
     * Urutan rapor = satu daftar bernomor per tingkat: mapel nomor 1 dicetak
     * paling atas. Panah ▲ = pindah satu langkah ke atas; bila mapel sudah
     * bernomor 1 ia otomatis jadi lebih atas lagi (0, -1, …). Panah ▼ = satu
     * langkah ke bawah (nomor bertambah). Tidak ada batas atas/bawah, jadi
     * mapel yang nomornya masih kosong tetap bisa dinaikkan/diturunkan.
     *
     * Mapel yang tergeser (yang tadi menempati posisi tujuan) ikut diturunkan/
     * dinaikkan satu langkah agar tidak terjadi nomor kembar.
     */
    public function moveStruktur(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = $request->user();

        abort_unless(
            app(KurikulumAccess::class)->isKurikulumTeam($user),
            403,
            'Hanya tim kurikulum (termasuk Waka/Kepala) yang dapat mengatur urutan dan status mapel rapor.'
        );

        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'subject_id' => 'required|exists:subjects,id',
            'direction' => 'required|in:up,down',
            'semester' => 'nullable|in:ganjil,genap',
        ]);

        $semester = $data['semester'] ?? 'ganjil';

        app(AcademicPeriodService::class)->assertNotLocked($data['academic_year_id'], 'Susunan Rapor Peta Kurikulum');

        $gradeLevel = GradeLevel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($data['grade_level_id']);

        $structures = GradeLevelSubject::query()
            ->where('grade_level_id', $gradeLevel->id)
            ->forAcademicYear($data['academic_year_id'])
            ->orderByAcademicYearFirst()
            ->get()
            ->unique('subject_id')
            ->filter(fn ($row) => $row->is_active)
            ->values();

        $orderService = app(RaporSubjectOrderService::class);

        /** @var \Illuminate\Support\Collection<int, Subject> $sorted */
        $sorted = $orderService->sortSubjects(
            Subject::query()->whereIn('id', $structures->pluck('subject_id'))->get(),
            $gradeLevel->id,
            $data['academic_year_id'],
        );

        $index = $sorted->search(fn (Subject $s) => (string) $s->id === (string) $data['subject_id']);
        abort_if($index === false, 404, 'Mapel ini tidak ada pada struktur tingkat tersebut.');

        $structuresBySubject = $structures->keyBy('subject_id');
        $moving = $structuresBySubject->get($data['subject_id']);
        abort_if(! $moving, 404, 'Mapel ini tidak ada pada struktur tingkat tersebut.');

        // Nomor urut dihitung dari posisi tampil supaya keadaan awal yang
        // belum diatur (semua kosong) pun langsung menjadi daftar bernomor.
        $positions = $sorted->values();
        $currentOrder = fn (int $position, Subject $subject): int
            => $structuresBySubject->get($subject->id)?->sort_order ?? $position + 1;

        $movingOrder = $currentOrder($index, $sorted->get($index));
        $step = $data['direction'] === 'up' ? -1 : 1;
        $newOrder = $movingOrder + $step;

        // Simpan seluruh daftar supaya nomor unik dan berurutan menurut baru.
        $moving->sort_order = $newOrder;
        $moving->save();

        foreach ($positions as $position => $subject) {
            if ((string) $subject->id === (string) $data['subject_id']) {
                continue;
            }

            $row = $structuresBySubject->get($subject->id);
            if (! $row) {
                continue;
            }

            $order = $currentOrder($position, $subject);

            // Mapel yang dilewati bergeser satu langkah ke arah berlawanan.
            if ($data['direction'] === 'up' && $order >= $newOrder && $order < $movingOrder) {
                $row->sort_order = $order + 1;
            } elseif ($data['direction'] === 'down' && $order > $movingOrder && $order <= $newOrder) {
                $row->sort_order = $order - 1;
            } else {
                // Semua mapel yang belum bernomor diberi nomor menurut posisi tampil.
                if ($row->sort_order === null) {
                    $row->sort_order = $position + 1;
                } elseif (! $row->isDirty()) {
                    continue;
                }
            }

            $row->save();
        }

        // Bila daftar mapel rapor tingkat belum pernah diatur, tetapkan seluruh
        // mapel dengan susunan baru agar cetakan langsung mengikutinya.
        if ($orderService->reportSubjectIds($schoolId, $data['academic_year_id'], $semester, $gradeLevel->id) === null) {
            $ids = $orderService
                ->sortSubjects(
                    Subject::query()->whereIn('id', $structures->pluck('subject_id'))->get(),
                    $gradeLevel->id,
                    $data['academic_year_id'],
                )
                ->pluck('id')
                ->all();

            $orderService->saveReportSubjects(
                $schoolId,
                $data['academic_year_id'],
                $semester,
                $gradeLevel->id,
                $ids,
            );
        }

        // Panah dipanggil lewat fetch(): kembalikan susunan terbaru agar baris
        // di layar langsung berpindah tanpa memuat ulang halaman.
        if ($request->expectsJson()) {
            return response()->json($this->urutanTingkat($schoolId, $gradeLevel->id, $data['academic_year_id'], $semester));
        }

        // Tanpa flash: panah dipakai berulang, dan tabel sudah langsung
        // menunjukkan urutan baru sehingga modal "Berhasil" hanya mengganggu.
        return back();
    }

    /**
     * Susunan rapor satu tingkat untuk konsumsi layar: ID mapel yang masuk
     * rapor (urutan cetak) beserta nomor urutnya masing-masing.
     *
     * @return array{subjects: array<int, array{id: string, sort_order: int|null}>, report_ids: array<int, string>|null}
     */
    private function urutanTingkat(?string $schoolId, string $gradeLevelId, string $academicYearId, string $semester): array
    {
        $orderService = app(RaporSubjectOrderService::class);

        $structures = GradeLevelSubject::query()
            ->where('grade_level_id', $gradeLevelId)
            ->forAcademicYear($academicYearId)
            ->orderByAcademicYearFirst()
            ->get()
            ->unique('subject_id');

        $sorted = $orderService->sortSubjects(
            Subject::query()->whereIn('id', $structures->pluck('subject_id'))->get(),
            $gradeLevelId,
            $academicYearId,
        );

        return [
            'subjects' => $sorted->map(fn (Subject $subject) => [
                'id' => $subject->id,
                'sort_order' => $structures->firstWhere('subject_id', $subject->id)?->sort_order,
            ])->values()->all(),
            'report_ids' => $orderService->reportSubjectIds($schoolId, $academicYearId, $semester, $gradeLevelId),
        ];
    }

    /**
     * Inti penyimpanan JP: satu nilai per (mapel × tingkat), disebar ke rombel
     * dan penugasan guru lewat SubjectHoursSynchronizer.
     *
     * @param  array<string, array<string, int|string|null>>  $jp
     * @return array{diubah: int, rombel: int, guru: int}
     */
    private function simpanJp(Request $request, ?string $schoolId, $user, string $academicYearId, array $jp): array
    {
        abort_unless(
            app(KurikulumAccess::class)->canManageSubjectHours($user),
            403,
            'Anda tidak berwenang mengatur JP Peta Kurikulum.'
        );

        // Hanya tingkat milik satuan pendidikan ini yang boleh diubah.
        $gradeLevelIds = GradeLevel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('id', array_keys($jp))
            ->pluck('id')
            ->all();

        abort_if($gradeLevelIds === [], 403, 'Tingkat yang dikirim bukan milik satuan pendidikan Anda.');

        $subjectIds = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->pluck('id')
            ->all();

        $synchronizer = app(SubjectHoursSynchronizer::class);
        $diubah = 0;
        $rombel = 0;
        $guru = 0;

        foreach ($jp as $gradeLevelId => $subjects) {
            if (! in_array($gradeLevelId, $gradeLevelIds, true)) {
                continue;
            }

            foreach ((array) $subjects as $subjectId => $hours) {
                if (! in_array($subjectId, $subjectIds, true)) {
                    continue;
                }

                $hasil = $synchronizer->applyGradeLevelHours(
                    $subjectId,
                    $gradeLevelId,
                    $academicYearId,
                    (int) $hours,
                );

                $diubah++;
                $rombel += $hasil['rombel'];
                $guru += $hasil['guru'];
            }
        }

        return ['diubah' => $diubah, 'rombel' => $rombel, 'guru' => $guru];
    }

    /**
     * Inti penyimpanan KKTP per (mapel × tingkat): satu nilai untuk seluruh
     * rombel di tingkat itu.
     *
     * @param  array<string, array<string, int|float|string|null>>  $kktp
     * @return array{saved: int, books: int}
     */
    private function simpanKktp(Request $request, ?string $schoolId, $user, string $academicYearId, string $semester, array $kktp): array
    {
        $isKoorMapelUser = (bool) optional($user->tugasTambahan)
            ->pluck('nama')
            ->contains(fn ($n) => str_contains(mb_strtolower((string) $n), 'koordinator guru'));

        abort_unless(
            app(KurikulumAccess::class)->isKurikulumTeam($user) || $isKoorMapelUser,
            403,
            'Hanya tim kurikulum atau koordinator mapel yang dapat mengatur KKTP.'
        );

        $gradeLevelIds = GradeLevel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('id', array_keys($kktp))
            ->pluck('id')
            ->all();

        abort_if($kktp !== [] && $gradeLevelIds === [], 403, 'Tingkat yang dikirim bukan milik satuan pendidikan Anda.');

        $kktpService = app(SubjectKktpService::class);
        $saved = 0;
        $books = 0;

        foreach ($kktp as $gradeLevelId => $subjects) {
            if (! in_array($gradeLevelId, $gradeLevelIds, true)) {
                continue;
            }

            $hasil = $kktpService->saveForGradeLevel(
                $schoolId,
                (string) $gradeLevelId,
                $academicYearId,
                $semester,
                (array) $subjects,
                $user->id,
            );

            $saved += $hasil['saved'];
            $books = $hasil['books'];
        }

        return ['saved' => $saved, 'books' => $books];
    }

    /**
     * Inti penyimpanan susunan rapor per tingkat: nomor urut, asal mapel
     * (Pondok/Dinas/Pondok + Dinas), dan daftar mapel yang dicetak.
     *
     * @param  array<string, array<string, array<string, mixed>>>  $struktur
     * @return int jumlah mapel-tingkat yang diproses
     */
    private function simpanStruktur(Request $request, ?string $schoolId, $user, string $academicYearId, string $semester, array $struktur): int
    {
        abort_unless(
            app(KurikulumAccess::class)->isKurikulumTeam($user),
            403,
            'Hanya tim kurikulum (termasuk Waka/Kepala) yang dapat mengatur urutan dan status mapel rapor.'
        );

        $gradeLevelIds = GradeLevel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('id', array_keys($struktur))
            ->pluck('id')
            ->all();

        abort_if($struktur !== [] && $gradeLevelIds === [], 403, 'Tingkat yang dikirim bukan milik satuan pendidikan Anda.');

        $orderService = app(RaporSubjectOrderService::class);
        $diubah = 0;

        foreach ($struktur as $gradeLevelId => $subjects) {
            if (! in_array($gradeLevelId, $gradeLevelIds, true)) {
                continue;
            }

            $subjectIds = array_keys((array) $subjects);

            $structures = GradeLevelSubject::query()
                ->where('grade_level_id', $gradeLevelId)
                ->whereIn('subject_id', $subjectIds)
                ->forAcademicYear($academicYearId)
                ->orderByAcademicYearFirst()
                ->get()
                ->unique('subject_id')
                ->keyBy('subject_id');

            $raporIds = [];

            foreach ((array) $subjects as $subjectId => $input) {
                $structure = $structures->get($subjectId);

                if (! $structure) {
                    continue;
                }

                $source = $input['sumber'] ?? null;
                $structure->subject_source = array_key_exists((string) $source, GradeLevelSubject::SOURCE_OPTIONS)
                    ? $source
                    : null;
                $structure->sort_order = isset($input['urutan']) && $input['urutan'] !== ''
                    ? (int) $input['urutan']
                    : null;

                if ($structure->isDirty()) {
                    $structure->save();
                }

                if ((string) ($input['rapor'] ?? '1') === '1') {
                    $raporIds[] = $subjectId;
                }

                $diubah++;
            }

            $orderService->saveReportSubjects(
                $schoolId,
                $academicYearId,
                $semester,
                $gradeLevelId,
                $raporIds,
            );
        }

        return $diubah;
    }
}
