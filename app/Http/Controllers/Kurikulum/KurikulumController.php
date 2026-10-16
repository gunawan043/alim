<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\StudyGroup;
use App\Models\SubjectKktp;
use App\Models\TeachingAssignment;
use App\Models\TujuanPembelajaran;
use App\Services\KurikulumAccess;
use App\Services\PekanEfektifService;
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

        $activeAy = ($ayWithAssignments ? $academicYears->firstWhere('id', $ayWithAssignments) : null)
            ?? $academicYears->firstWhere('is_active', true)
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
                ->where('is_active', true)
                ->forAcademicYear($academicYearId)
                ->orderByAcademicYearFirst()
                ->get()
                ->groupBy('grade_level_id')
                ->map(fn ($rows) => $rows
                    ->unique(fn ($r) => $r->subject_id.'|'.($r->track ?? ''))
                    ->values());

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
                ->get(['subject_id', 'fase'])
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
                    ]);
                }
            }
        }

        // ── KKTP per (mapel × tingkat) untuk TA/semester terpilih ──
        $kktpMap = SubjectKktp::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->get(['subject_id', 'grade_level_id', 'kktp_score'])
            ->keyBy(fn ($k) => $k->subject_id.'|'.$k->grade_level_id);

        $rows = $rows->map(function ($row) use ($kktpMap) {
            $row['kktp'] = $kktpMap->get($row['subject_id'].'|'.$row['grade_level_id'])?->kktp_score;

            return $row;
        });

        // Izin atur KKTP: tim kurikulum atau koordinator mapel.
        $isKoorMapelUser = (bool) optional($request->user()?->tugasTambahan)
            ->pluck('nama')
            ->contains(fn ($n) => str_contains(mb_strtolower((string) $n), 'koordinator guru'));
        $canEditKktp = app(KurikulumAccess::class)->isKurikulumTeam($request->user()) || $isKoorMapelUser;

        // ── Total JP per tingkat (struktur periode terpilih, bukan baris tampil) ──
        $jpTotals = GradeLevelSubject::query()
            ->when($schoolId, fn ($q) => $q->whereHas('gradeLevel', fn ($g) => $g->where('school_id', $schoolId)))
            ->where('is_active', true)
            ->forAcademicYear($academicYearId)
            ->orderByAcademicYearFirst()
            ->get(['grade_level_id', 'subject_id', 'track', 'allocation_hours', 'academic_year_id'])
            ->groupBy('grade_level_id')
            ->map(fn ($items) => $items
                ->unique(fn ($r) => $r->subject_id.'|'.($r->track ?? ''))
                ->sum('allocation_hours'));

        $jpSummary = $gradeLevelOptions->map(fn ($g) => [
            'name' => $g->name,
            'total' => (float) ($jpTotals[$g->id] ?? 0),
        ])->values();

        // Izin menyiapkan Peta TA baru: editor kurikulum (tim/koordinator kurikulum, super admin).
        $canPrepare = app(KurikulumAccess::class)->isKurikulumEditor($request->user());

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
            'canPrepare',
            'isArchive',
            'activeAcademicYearId',
            'jpSummary'
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

        $targetAyId = $data['academic_year_id'];
        $sourceAyId = $data['source_academic_year_id'];

        $createdStructure = 0;
        $createdKktp = 0;

        DB::transaction(function () use ($schoolId, $targetAyId, $sourceAyId, $user, &$createdStructure, &$createdKktp) {
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
        });

        $targetAy = AcademicYear::find($targetAyId);

        return back()->with(
            'success',
            "Peta Kurikulum {$targetAy?->name} disiapkan: {$createdStructure} baris struktur dan {$createdKktp} KKTP disalin (TA sumber tidak diubah)."
        );
    }

    /**
     * Simpan KKTP (Kriteria Ketercapaian) langsung dari Peta Kurikulum.
     * Format: kktp[grade_level_id][subject_id] = nilai 0-100 (kosong = hapus).
     */
    public function saveKktp(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = $request->user();

        $isKoorMapelUser = (bool) optional($user->tugasTambahan)
            ->pluck('nama')
            ->contains(fn ($n) => str_contains(mb_strtolower((string) $n), 'koordinator guru'));

        abort_unless(
            app(KurikulumAccess::class)->isKurikulumTeam($user) || $isKoorMapelUser,
            403,
            'Hanya tim kurikulum atau koordinator mapel yang dapat mengatur KKTP.'
        );

        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'kktp' => 'nullable|array',
            'kktp.*' => 'nullable|array',
            'kktp.*.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $saved = 0;

        foreach (($data['kktp'] ?? []) as $gradeLevelId => $subjects) {
            foreach ((array) $subjects as $subjectId => $value) {
                $query = SubjectKktp::query()
                    ->where('subject_id', $subjectId)
                    ->where('grade_level_id', $gradeLevelId)
                    ->where('academic_year_id', $data['academic_year_id'])
                    ->where('semester', $data['semester']);

                if ($value === null || $value === '') {
                    $query->delete();

                    continue;
                }

                SubjectKktp::updateOrCreate(
                    [
                        'subject_id' => $subjectId,
                        'grade_level_id' => $gradeLevelId,
                        'academic_year_id' => $data['academic_year_id'],
                        'semester' => $data['semester'],
                    ],
                    [
                        'school_id' => $schoolId,
                        'kktp_score' => $value,
                        'kkm_score' => $value, // konvensi: kkm mirror KKTP
                        'created_by' => $user->id,
                    ]
                );

                $saved++;
            }
        }

        return back()->with('success', "KKTP tersimpan ({$saved} mapel-tingkat).");
    }
}
