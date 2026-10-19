<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupAttendance;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzSchedule;
use App\Services\TahfidzAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TahfidzAttendanceController extends Controller
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
        $date = $request->input('date', now()->toDateString());
        $sessionType = in_array($request->input('session_type'), ['setoran', 'murajaah', 'tasmi', 'evaluasi', 'tikror'], true)
            ? $request->input('session_type') : 'setoran';

        $members = collect();
        $attendance = collect();
        $schedule = null;
        $holiday = null;
        $stats = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'belum' => 0];
        $canInput = false;

        if ($group) {
            $members = TahfidzGroupMember::with('student:id,name,nis')
                ->where('tahfidz_group_id', $group->id)
                ->where('status', 'aktif')
                ->get()
                ->sortBy(fn ($member) => $member->student?->name)
                ->values();

            $attendance = TahfidzGroupAttendance::where('tahfidz_group_id', $group->id)
                ->whereDate('attendance_date', $date)
                ->where('session_type', $sessionType)
                ->get()
                ->keyBy('student_id');

            $schedule = TahfidzSchedule::where('tahfidz_group_id', $group->id)
                ->where('day_of_week', Carbon::parse($date)->dayOfWeekIso)
                ->where('is_active', true)
                ->where(function ($query) use ($date) {
                    $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date);
                })
                ->where(function ($query) use ($date) {
                    $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date);
                })
                ->orderBy('time_start')
                ->first();

            $holiday = DB::table('academic_calendars')
                ->where(function ($query) use ($group) {
                    $query->where('school_id', $group->school_id)->orWhere('is_all_schools', 1);
                })
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->where('event_type', 'like', 'libur%')
                ->first();

            foreach ($members as $member) {
                $status = $attendance->get($member->student_id)?->status;
                if ($status && array_key_exists($status, $stats)) {
                    $stats[$status]++;
                } else {
                    $stats['belum']++;
                }
            }

            $canInput = $this->access->canInputSetoran($request->user(), $group);
        }

        return view('tahfidz.attendance.index', compact(
            'userId', 'groups', 'group', 'date', 'sessionType', 'members',
            'attendance', 'schedule', 'holiday', 'stats', 'canInput', 'academicYear'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'tahfidz_group_id' => 'required|exists:tahfidz_groups,id',
            'attendance_date' => 'required|date',
            'session_type' => 'required|in:setoran,murajaah,tasmi,evaluasi,tikror',
            'statuses' => 'required|array',
            'statuses.*' => 'required|in:hadir,izin,sakit,alpa',
            'notes' => 'nullable|array',
            'notes.*' => 'nullable|string|max:1000',
        ]);

        $group = TahfidzGroup::findOrFail($validated['tahfidz_group_id']);
        abort_unless($this->access->canInputSetoran($request->user(), $group), 403, 'Anda tidak berwenang mengisi absensi halaqah ini.');

        $memberIds = TahfidzGroupMember::where('tahfidz_group_id', $group->id)
            ->where('status', 'aktif')
            ->pluck('student_id')
            ->all();

        foreach ($validated['statuses'] as $studentId => $status) {
            if (! in_array($studentId, $memberIds, true)) {
                continue;
            }

            $existing = TahfidzGroupAttendance::where('tahfidz_group_id', $group->id)
                ->where('student_id', $studentId)
                ->where('session_type', $validated['session_type'])
                ->whereDate('attendance_date', $validated['attendance_date'])
                ->first();

            $payload = [
                'academic_year_id' => $group->academic_year_id,
                'status' => $status,
                'notes' => $validated['notes'][$studentId] ?? null,
                'recorded_by' => $request->user()?->id,
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                TahfidzGroupAttendance::create(array_merge($payload, [
                    'tahfidz_group_id' => $group->id,
                    'student_id' => $studentId,
                    'attendance_date' => $validated['attendance_date'],
                    'session_type' => $validated['session_type'],
                ]));
            }
        }

        return redirect()
            ->route('user.tahfidz.attendance.index', [
                'userId' => $userId,
                'group_id' => $group->id,
                'date' => $validated['attendance_date'],
                'session_type' => $validated['session_type'],
            ])
            ->with('success', 'Absensi halaqah tersimpan.');
    }

    private function accessibleGroups(Request $request, ?string $academicYearId)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?: $user?->employment?->school_id;
        $query = TahfidzGroup::with('school:id,name')->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId));

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId) || $this->access->isSchoolLeader($user)) {
                $query->where('school_id', $schoolId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)->orWhere('coordinator_id', $user->id)
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
            'Anda tidak berwenang melihat absensi halaqah.'
        );
    }
}
