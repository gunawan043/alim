<?php

namespace App\Services\Academic;

use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\PerangkatPembelajaran;
use App\Models\Prosem;
use App\Models\Prota;
use App\Models\RaportRegistration;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\TeacherAdminBook;
use App\Models\TujuanPembelajaran;
use Illuminate\Support\Facades\DB;

/**
 * Audit keterhubungan kurikulum dan kesiapan akademik.
 *
 * Rantai yang diperiksa: CP → TP → ATP → Prota → Prosem → Perangkat
 * → Jurnal → Buku Nilai → Rapor, serta konsistensi JP antara
 * study_group_subjects, SK (teaching_assignments), dan jadwal.
 */
class CurriculumConsistencyService
{
    public function build(array $schoolIds, ?string $academicYearId, string $semester, ?string $gradeLevelId = null): array
    {
        $year = $academicYearId ? AcademicYear::find($academicYearId) : null;
        $yearIds = $year
            ? AcademicYear::where('name', $year->name)->pluck('id')->all()
            : ($academicYearId ? [$academicYearId] : []);

        $groups = StudyGroup::query()
            ->with(['gradeLevel:id,name,level', 'homeroomTeacher:id,name'])
            ->whereIn('school_id', $schoolIds)
            ->whereIn('academic_year_id', $yearIds)
            ->when($gradeLevelId, fn ($q) => $q->where('grade_level_id', $gradeLevelId))
            ->where('is_active', true)
            ->get();

        $groupIds = $groups->pluck('id')->all();

        $planned = StudyGroupSubject::query()
            ->whereIn('study_group_id', $groupIds)
            ->where('is_active', true)
            ->get(['study_group_id', 'subject_id', 'weekly_hours'])
            ->keyBy(fn ($row) => $row->study_group_id.'|'.$row->subject_id);

        $sk = DB::table('teaching_assignments')
            ->whereIn('study_group_id', $groupIds)
            ->whereIn('academic_year_id', $yearIds)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->selectRaw('study_group_id, subject_id, SUM(weekly_hours) as hours')
            ->groupBy('study_group_id', 'subject_id')
            ->get()
            ->keyBy(fn ($row) => $row->study_group_id.'|'.$row->subject_id);

        $schedule = DB::table('jadwal_kbms')
            ->whereIn('study_group_id', $groupIds)
            ->whereIn('academic_year_id', $yearIds)
            ->where('is_active', 1)
            ->selectRaw('study_group_id, subject_id, COUNT(*) as slots')
            ->groupBy('study_group_id', 'subject_id')
            ->get()
            ->keyBy(fn ($row) => $row->study_group_id.'|'.$row->subject_id);

        $prota = Prota::query()
            ->whereIn('school_id', $schoolIds)
            ->whereIn('academic_year_id', $yearIds)
            ->where('semester', $semester)
            ->when($gradeLevelId, fn ($q) => $q->where('grade_level_id', $gradeLevelId))
            ->get(['grade_level_id', 'subject_id', 'jp_per_minggu'])
            ->keyBy(fn ($row) => $row->grade_level_id.'|'.$row->subject_id);

        $rows = collect();
        $keys = collect($planned->keys())
            ->merge($sk->keys())
            ->merge($schedule->keys())
            ->unique();

        $groupMap = $groups->keyBy('id');

        foreach ($keys as $key) {
            [$groupId, $subjectId] = explode('|', $key);
            $group = $groupMap->get($groupId);
            if (! $group) {
                continue;
            }

            $plan = (float) ($planned->get($key)->weekly_hours ?? 0);
            $skHours = (float) ($sk->get($key)->hours ?? 0);
            $slots = (int) ($schedule->get($key)->slots ?? 0);
            $protaHours = (float) ($prota->get(($group->grade_level_id ?? '').'|'.$subjectId)->jp_per_minggu ?? 0);
            $subjectName = DB::table('subjects')->where('id', $subjectId)->value('name') ?? 'Mapel';

            $issues = [];
            if ($plan <= 0) {
                $issues[] = 'JP rombel kosong';
            }
            if ($skHours <= 0) {
                $issues[] = 'Belum ada JP SK';
            } elseif ($plan > 0 && abs($skHours - $plan) > 0.01) {
                $issues[] = 'JP SK ≠ JP rombel';
            }
            if ($slots <= 0) {
                $issues[] = 'Belum ada jadwal';
            } elseif ($plan > 0 && $slots !== (int) $plan) {
                $issues[] = 'Slot jadwal ≠ JP';
            }
            if ($protaHours > 0 && $plan > 0 && abs($protaHours - $plan) > 0.01) {
                $issues[] = 'JP Prota ≠ JP rombel';
            }

            $rows->push([
                'group' => $group->name,
                'grade' => $group->gradeLevel?->name,
                'subject' => $subjectName,
                'planned' => $plan,
                'sk' => $skHours,
                'schedule' => $slots,
                'prota' => $protaHours ?: null,
                'homeroom' => $group->homeroomTeacher?->name,
                'status' => $issues === [] ? 'ok' : 'warning',
                'issues' => $issues,
            ]);
        }

        $teacherWorkload = DB::table('teaching_assignments as ta')
            ->join('users as u', 'u.id', '=', 'ta.teacher_id')
            ->whereIn('ta.school_id', $schoolIds)
            ->whereIn('ta.academic_year_id', $yearIds)
            ->where('ta.status', 'active')
            ->whereNull('ta.deleted_at')
            ->selectRaw('ta.teacher_id, u.name, SUM(ta.weekly_hours) as total_hours, COUNT(DISTINCT ta.study_group_id) as groups')
            ->groupBy('ta.teacher_id', 'u.name')
            ->orderByDesc('total_hours')
            ->get()
            ->map(fn ($row) => [
                'teacher' => $row->name,
                'hours' => (int) $row->total_hours,
                'groups' => (int) $row->groups,
            ]);

        $coverage = [
            'cp' => CapaianPembelajaran::whereIn('school_id', $schoolIds)->whereIn('academic_year_id', $yearIds)->where('is_active', true)->count(),
            'tp' => TujuanPembelajaran::whereIn('school_id', $schoolIds)->whereIn('academic_year_id', $yearIds)->where('is_active', true)->count(),
            'atp' => AlurTujuanPembelajaran::whereIn('school_id', $schoolIds)->whereIn('academic_year_id', $yearIds)->count(),
            'prota' => Prota::whereIn('school_id', $schoolIds)->whereIn('academic_year_id', $yearIds)->where('semester', $semester)->count(),
            'prosem' => Prosem::whereIn('school_id', $schoolIds)->whereIn('academic_year_id', $yearIds)->where('semester', $semester)->count(),
            'perangkat' => PerangkatPembelajaran::whereIn('school_id', $schoolIds)->whereIn('academic_year_id', $yearIds)->where('semester', $semester)->count(),
            'jurnal' => DB::table('admin_jurnal_pembelajaran')->whereIn('academic_year_id', $yearIds)->where('semester', $semester)->count(),
            'buku_nilai' => TeacherAdminBook::whereIn('school_id', $schoolIds)->whereIn('academic_year_id', $yearIds)->where('semester', $semester)->where('is_active', true)->count(),
        ];

        $students = StudentClassHistory::query()
            ->whereIn('study_group_id', $groupIds)
            ->whereIn('academic_year_id', $yearIds)
            ->where('is_active', true)
            ->pluck('student_id')
            ->unique();

        $readiness = [
            'students' => $students->count(),
            'groups' => $groups->count(),
            'groups_with_homeroom' => $groups->whereNotNull('homeroom_teacher_id')->count(),
            'books' => $coverage['buku_nilai'],
            'rapor_registered' => RaportRegistration::whereIn('student_id', $students)->count(),
            'tahfidz_progress' => DB::table('tahfidz_juz_progress')->whereIn('student_id', $students)->count(),
        ];
        $readiness['rate'] = $students->count() > 0
            ? round(min(100, ($readiness['rapor_registered'] / $students->count()) * 100), 2)
            : null;

        return [
            'year' => $year,
            'semester' => $semester,
            'groups' => $groups,
            'rows' => $rows->sortBy('group')->values(),
            'mismatches' => $rows->where('status', 'warning')->values(),
            'teacher_workload' => $teacherWorkload,
            'coverage' => $coverage,
            'readiness' => $readiness,
            'counts' => [
                'rows' => $rows->count(),
                'ok' => $rows->where('status', 'ok')->count(),
                'warning' => $rows->where('status', 'warning')->count(),
            ],
        ];
    }
}
