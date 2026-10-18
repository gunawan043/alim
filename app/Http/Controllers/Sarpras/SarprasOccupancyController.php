<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\WorkUnit;
use Illuminate\Http\Request;

class SarprasOccupancyController extends SarprasBaseController
{
    /**
     * Monitoring kepadatan rombel dan kamar asrama untuk kebutuhan
     * pendataan Sarpras dan Kebersihan oleh TU/Kepala URT.
     */
    public function index(Request $request)
    {
        abort_unless(
            $this->isUrtLeadership(),
            403,
            'Monitoring rombel dan kamar hanya dapat diakses TU/Kepala Unit Rumah Tangga.'
        );

        $activeYear = AcademicYear::query()->where('is_active', true)->first();
        $schoolId = $request->input('school_id');
        $dormitoryId = $request->input('dormitory_id');
        $search = trim((string) $request->input('search'));

        $schools = School::orderBy('name')->get(['id', 'name', 'work_unit_id']);
        $dormitories = Dormitory::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name', 'school_id']);

        $rombels = StudyGroup::with(['school', 'gradeLevel', 'homeroomTeacher'])
            ->withCount([
                'studentClassHistories as students_count' => function ($query) use ($activeYear) {
                    $query->where('is_active', true)
                        ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
                        ->whereHas('student', fn ($student) => $student->where('status', 'active'));
                },
            ])
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('homeroomTeacher', fn ($teacher) => $teacher->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderedByGrade()
            ->get();

        $dormRooms = DormitoryRoom::with(['dormitory', 'wing', 'activeSupervisor.user'])
            ->withCount([
                'residents as residents_count' => function ($query) use ($activeYear) {
                    $query->where('is_active', true)
                        ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
                        ->whereHas('student', fn ($student) => $student->where('status', 'active'));
                },
            ])
            ->where('is_active', true)
            ->when($dormitoryId, fn ($q) => $q->where('dormitory_id', $dormitoryId))
            ->when($schoolId, fn ($q) => $q->whereHas('dormitory', fn ($dorm) => $dorm->where('school_id', $schoolId)))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('dormitory', fn ($dorm) => $dorm->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('dormitory_id')
            ->orderBy('code')
            ->get();

        $totalStudents = Student::query()
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->count();

        $rombelStudentIds = StudentClassHistory::query()
            ->where('is_active', true)
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->when($schoolId, fn ($q) => $q->whereHas('studyGroup', fn ($group) => $group->where('school_id', $schoolId)))
            ->pluck('student_id')
            ->unique();

        $withoutRombel = Student::query()
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($rombelStudentIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $rombelStudentIds))
            ->count();

        $residentStudentIds = DormitoryResident::query()
            ->where('is_active', true)
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->when($dormitoryId, fn ($q) => $q->where('dormitory_id', $dormitoryId))
            ->when($schoolId, fn ($q) => $q->whereHas('dormitory', fn ($dorm) => $dorm->where('school_id', $schoolId)))
            ->pluck('student_id')
            ->unique();

        $withoutRoom = Student::query()
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($residentStudentIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $residentStudentIds))
            ->count();

        $stats = [
            'students' => $totalStudents,
            'rombels' => $rombels->count(),
            'class_capacity' => (int) $rombels->sum('capacity'),
            'class_filled' => (int) $rombels->sum('students_count'),
            'without_rombel' => $withoutRombel,
            'dorm_rooms' => $dormRooms->count(),
            'dorm_capacity' => (int) $dormRooms->sum('capacity'),
            'dorm_residents' => (int) $dormRooms->sum('residents_count'),
            'without_room' => $withoutRoom,
        ];

        $unitSummary = WorkUnit::query()
            ->withCount([
                'assets',
                'assetRooms',
                'dormitoryRooms as dorm_rooms_count' => fn ($q) => $q->where('dormitory_rooms.is_active', true),
                'dormitoryResidents as residents_count' => fn ($q) => $q->where('dormitory_residents.is_active', true)
                    ->when($activeYear, fn ($q2) => $q2->where('dormitory_residents.academic_year_id', $activeYear->id)),
            ])
            ->when($schoolId, fn ($q) => $q->where('id', School::query()->whereKey($schoolId)->value('work_unit_id')))
            ->orderBy('name')
            ->get();

        return view('sarpras.monitoring.rombel-kamar', compact(
            'activeYear',
            'schools',
            'dormitories',
            'rombels',
            'dormRooms',
            'stats',
            'unitSummary'
        ));
    }
}
