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
                ->get()
                ->groupBy('grade_level_id');

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

                    $weeklyHours = $this->hoursResolver->resolve(null, $subject, null, $grade->id);
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

        // ── Total JP per tingkat (struktur, bukan per baris tampil) ──
        $jpTotals = GradeLevelSubject::query()
            ->when($schoolId, fn ($q) => $q->whereHas('gradeLevel', fn ($g) => $g->where('school_id', $schoolId)))
            ->where('is_active', true)
            ->get(['grade_level_id', 'subject_id', 'allocation_hours'])
            ->groupBy('grade_level_id')
            ->map(fn ($items) => $items->groupBy('subject_id')->map->max('allocation_hours')->sum());

        $jpSummary = $gradeLevelOptions->map(fn ($g) => [
            'name' => $g->name,
            'total' => (float) ($jpTotals[$g->id] ?? 0),
        ])->values();

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
            'jpSummary'
        ));
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
