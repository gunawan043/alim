<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupAttendance;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzSetoran;
use App\Services\TahfidzAccess;
use Illuminate\Http\Request;

class TahfidzWorkspaceController extends Controller
{
    public function __construct(private readonly TahfidzAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?: $user?->employment?->school_id;

        abort_unless(
            $this->access->canViewAllSchools($user)
            || $this->access->isSchoolCoordinator($user, $schoolId)
            || $this->access->isSchoolLeader($user)
            || $this->access->isTahfidzTeacher($user),
            403,
            'Anda tidak berwenang membuka Buku Kerja Tahfidz.'
        );

        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();

        $groups = TahfidzGroup::with([
                'school:id,name',
                'studyGroup:id,name',
                'teacher:id,name',
                'coordinator:id,name',
                'schedules' => fn ($query) => $query->where('is_active', true)
                    ->where('day_of_week', now()->dayOfWeekIso)
                    ->orderBy('time_start'),
            ])
            ->withCount(['members as active_members_count' => fn ($query) => $query->where('status', 'aktif')])
            ->where('is_active', true)
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->when(! $this->access->canViewAllSchools($user), function ($query) use ($user, $schoolId) {
                if ($this->access->isSchoolCoordinator($user, $schoolId) || $this->access->isSchoolLeader($user)) {
                    $query->where('school_id', $schoolId);
                } else {
                    $query->where(function ($own) use ($user) {
                        $own->where('teacher_id', $user->id)
                            ->orWhere('coordinator_id', $user->id)
                            ->orWhereHas('teachers', fn ($teacher) => $teacher->where('user_id', $user->id)->where('is_active', true));
                    });
                }
            })
            ->orderBy('name')
            ->get();

        $groupIds = $groups->pluck('id');
        $studentIds = TahfidzGroupMember::whereIn('tahfidz_group_id', $groupIds)
            ->where('status', 'aktif')
            ->distinct()
            ->pluck('student_id');

        $stats = [
            'groups' => $groups->count(),
            'students' => $studentIds->count(),
            'setoran_today' => TahfidzSetoran::whereIn('tahfidz_group_id', $groupIds)
                ->whereDate('setoran_date', now()->toDateString())->count(),
            'attendance_today' => TahfidzGroupAttendance::whereIn('tahfidz_group_id', $groupIds)
                ->whereDate('attendance_date', now()->toDateString())->count(),
        ];

        return view('tahfidz.workspace.index', compact('userId', 'academicYear', 'groups', 'stats'));
    }
}
