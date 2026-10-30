<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupAttendance;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzProgressRecap;
use App\Models\TahfidzSetoran;
use App\Services\TahfidzAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TahfidzReportController extends Controller
{
    public function __construct(private readonly TahfidzAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();
        $groups = $this->accessibleGroups($request, $academicYear?->id);
        $groupId = $request->input('group_id', $groups->first()?->id);
        $group = $groups->firstWhere('id', $groupId);

        $monthInput = (string) $request->input('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthInput)) {
            $monthInput = now()->format('Y-m');
        }
        $month = Carbon::createFromFormat('!Y-m-d', $monthInput.'-01')->startOfMonth();
        $monthLabel = $month->copy()->locale('id')->translatedFormat('F Y');
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $members = collect();
        $setorans = collect();
        $attendance = collect();
        $recaps = collect();
        $daily = array_fill(1, $month->daysInMonth, 0);
        $typeCounts = ['ziyadah' => 0, 'tikrar' => 0, 'murajaah' => 0, 'robet' => 0];
        $attendanceCounts = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'belum_setor' => 0, 'belum_siap' => 0];
        $studentRows = collect();

        if ($group) {
            $members = TahfidzGroupMember::with('student:id,name,nis')
                ->where('tahfidz_group_id', $group->id)
                ->where('status', 'aktif')
                ->get()
                ->sortBy(fn ($member) => $member->student?->name)
                ->values();

            $studentIds = $members->pluck('student_id');
            $setorans = TahfidzSetoran::query()
                ->where('tahfidz_group_id', $group->id)
                ->whereIn('student_id', $studentIds)
                ->whereBetween('setoran_date', [$start->toDateString(), $end->toDateString()])
                ->get();

            $attendance = TahfidzGroupAttendance::query()
                ->where('tahfidz_group_id', $group->id)
                ->whereIn('student_id', $studentIds)
                ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                ->get();

            $recaps = TahfidzProgressRecap::query()
                ->whereIn('student_id', $studentIds)
                ->where('academic_year_id', $group->academic_year_id)
                ->get()
                ->keyBy('student_id');

            foreach ($setorans as $setoran) {
                $day = (int) $setoran->setoran_date?->format('j');
                if (isset($daily[$day])) {
                    $daily[$day]++;
                }
                $type = $setoran->setoran_type ?: 'ziyadah';
                $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
            }

            foreach ($attendance as $row) {
                $status = $row->status ?: 'hadir';
                $attendanceCounts[$status] = ($attendanceCounts[$status] ?? 0) + 1;
            }

            $setoranByStudent = $setorans->groupBy('student_id');
            $attendanceByStudent = $attendance->groupBy('student_id');
            $studentRows = $members->map(function ($member) use ($setoranByStudent, $attendanceByStudent, $recaps) {
                $studentSetoran = $setoranByStudent->get($member->student_id, collect());
                $studentAttendance = $attendanceByStudent->get($member->student_id, collect());
                $totalAttendance = $studentAttendance->count();
                $hadir = $studentAttendance->where('status', 'hadir')->count();
                $recap = $recaps->get($member->student_id);

                return [
                    'student' => $member->student,
                    'setoran' => $studentSetoran->count(),
                    'types' => $studentSetoran->groupBy('setoran_type')->map->count(),
                    'attendance' => $hadir,
                    'attendance_total' => $totalAttendance,
                    'attendance_percent' => $totalAttendance > 0 ? round(($hadir / $totalAttendance) * 100, 1) : null,
                    'target_percent' => $recap?->pencapaian_target_persen,
                ];
            })->values();
        }

        $totalAttendance = array_sum($attendanceCounts);
        $stats = [
            'members' => $members->count(),
            'setoran' => $setorans->count(),
            'attendance_rate' => $totalAttendance > 0
                ? round(($attendanceCounts['hadir'] / $totalAttendance) * 100, 1)
                : null,
            'target_avg' => $studentRows->whereNotNull('target_percent')->avg('target_percent'),
        ];

        return view('tahfidz.reports.index', compact(
            'userId', 'academicYear', 'groups', 'group', 'groupId',
            'monthInput', 'month', 'monthLabel', 'members', 'setorans',
            'attendance', 'daily', 'typeCounts', 'attendanceCounts',
            'studentRows', 'stats'
        ));
    }

    private function accessibleGroups(Request $request, ?string $academicYearId): Collection
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?: $user?->employment?->school_id;
        $query = TahfidzGroup::with(['school:id,name', 'studyGroup:id,name'])
            ->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId));

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId)
                || $this->access->isSchoolLeader($user)) {
                $query->where('school_id', $schoolId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)
                        ->orWhere('coordinator_id', $user->id)
                        ->orWhereHas('teachers', fn ($teacher) => $teacher->where('user_id', $user->id)->where('is_active', true));
                });
            }
        }

        return $query->orderBy('name')->get();
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?: $user?->employment?->school_id;

        abort_unless(
            $this->access->canViewAllSchools($user)
            || $this->access->isSchoolCoordinator($user, $schoolId)
            || $this->access->isSchoolLeader($user)
            || $this->access->isTahfidzTeacher($user)
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang melihat laporan Tahfidz.'
        );
    }
}
