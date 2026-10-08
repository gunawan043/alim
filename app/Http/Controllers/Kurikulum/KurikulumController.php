<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\StudyGroup;
use App\Models\TeachingAssignment;
use App\Models\TujuanPembelajaran;
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
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

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

        if ($academicYearId) {
            $gradeLevels = GradeLevel::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
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

            $subjectIds = $gradeSubjects->flatten()->pluck('subject_id')->filter()->unique()->values()->all();

            $teachersBySubject = TeachingAssignment::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('academic_year_id', $academicYearId)
                ->where('status', 'active')
                ->whereIn('subject_id', $subjectIds)
                ->get(['subject_id', 'teacher_id'])
                ->groupBy('subject_id')
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
                        'guru' => (int) ($teachersBySubject[$subject->id] ?? 0),
                        'cp_count' => $fase ? (int) ($cpCounts[$subject->id.'|'.$fase] ?? 0) : 0,
                        'tp_count' => (int) ($tpCounts[$subject->id] ?? 0),
                        'atp_id' => $atp?->id,
                        'atp_total_jp' => $atp?->total_jp,
                        'atp_status' => $atp?->status,
                    ]);
                }
            }
        }

        return view('kurikulum.index', compact(
            'userId',
            'academicYears',
            'academicYearId',
            'semester',
            'mingguEfektif',
            'ringkasan',
            'rows'
        ));
    }
}
