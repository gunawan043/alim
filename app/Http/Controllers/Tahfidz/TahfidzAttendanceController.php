<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupAttendance;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzSchedule;
use App\Services\TahfidzAccess;
use App\Services\TahfidzPresenceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TahfidzAttendanceController extends Controller
{
    private const SESSION_TYPES = ['setoran', 'murajaah', 'tasmi', 'evaluasi', 'tikror'];

    public function __construct(private readonly TahfidzAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();
        $groups = $this->accessibleGroups($request, $academicYear?->id);
        $groupId = $request->input('group_id', $groups->first()?->id);
        $group = $groups->firstWhere('id', $groupId);

        try {
            $date = Carbon::parse($request->input('date', now()->toDateString()));
        } catch (\Throwable) {
            $date = now();
        }
        $dateString = $date->toDateString();

        $sessionType = in_array($request->input('session_type'), self::SESSION_TYPES, true)
            ? $request->input('session_type')
            : 'setoran';

        $monthInput = (string) $request->input('month', $date->format('Y-m'));
        $month = preg_match('/^\d{4}-\d{2}$/', $monthInput) ? $monthInput : $date->format('Y-m');
        $tab = in_array($request->input('tab'), ['input', 'riwayat', 'rekap'], true)
            ? $request->input('tab')
            : 'input';

        $members = collect();
        $attendance = collect();
        $presence = collect();
        $schedule = null;
        $holiday = null;
        $stats = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'belum_setor' => 0, 'belum_siap' => 0];
        $history = collect();
        $monthOptions = collect([$month]);
        $meetingDates = collect();
        $recapRows = collect();
        $recapTotals = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'belum_setor' => 0, 'belum_siap' => 0, 'meetings' => 0];
        $canInput = false;

        if ($group) {
            $members = TahfidzGroupMember::with('student:id,name,nis')
                ->where('tahfidz_group_id', $group->id)
                ->where('status', 'aktif')
                ->get()
                ->sortBy(fn ($member) => $member->student?->name)
                ->values();

            $attendance = TahfidzGroupAttendance::where('tahfidz_group_id', $group->id)
                ->whereDate('attendance_date', $dateString)
                ->where('session_type', $sessionType)
                ->get()
                ->keyBy('student_id');

            $presence = app(TahfidzPresenceService::class)
                ->forStudents($members->pluck('student_id'), $date);

            $schedule = TahfidzSchedule::where('tahfidz_group_id', $group->id)
                ->where('day_of_week', $date->dayOfWeekIso)
                ->where('is_active', true)
                ->where(function ($query) use ($dateString) {
                    $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $dateString);
                })
                ->where(function ($query) use ($dateString) {
                    $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $dateString);
                })
                ->orderBy('time_start')
                ->first();

            $holiday = DB::table('academic_calendars')
                ->where(function ($query) use ($group) {
                    $query->where('school_id', $group->school_id)->orWhere('is_all_schools', 1);
                })
                ->whereDate('start_date', '<=', $dateString)
                ->whereDate('end_date', '>=', $dateString)
                ->where('event_type', 'like', 'libur%')
                ->first();

            foreach ($members as $member) {
                $presenceRow = $presence->get((string) $member->student_id);
                $status = $attendance->get($member->student_id)?->status
                    ?? ($presenceRow['attendance_status'] ?? null)
                    ?? 'belum_setor';

                if (array_key_exists($status, $stats)) {
                    $stats[$status]++;
                }
            }

            $canInput = $this->access->canInputSetoran($request->user(), $group);

            // Satu query untuk riwayat, pilihan bulan, dan rekap bulanan.
            $allRecords = TahfidzGroupAttendance::where('tahfidz_group_id', $group->id)
                ->where('session_type', $sessionType)
                ->orderByDesc('attendance_date')
                ->get(['student_id', 'attendance_date', 'status', 'notes']);

            $monthOptions = $allRecords
                ->toBase()
                ->map(fn ($record) => $record->attendance_date->format('Y-m'))
                ->push($month)
                ->unique()
                ->sortDesc()
                ->values();

            $history = $allRecords
                ->groupBy(fn ($record) => $record->attendance_date->format('Y-m-d'))
                ->map(fn (Collection $rows, string $recordDate) => (object) [
                    'date' => Carbon::parse($recordDate),
                    'hadir' => $rows->where('status', 'hadir')->count(),
                    'izin' => $rows->where('status', 'izin')->count(),
                    'sakit' => $rows->where('status', 'sakit')->count(),
                    'alpa' => $rows->where('status', 'alpa')->count(),
                    'belum_setor' => $rows->where('status', 'belum_setor')->count(),
                    'belum_siap' => $rows->where('status', 'belum_siap')->count(),
                    'total' => $rows->count(),
                ])
                ->sortByDesc(fn ($row) => $row->date)
                ->values();

            $monthRecords = $allRecords->filter(
                fn ($record) => $record->attendance_date->format('Y-m') === $month
            );

            $meetingDates = $monthRecords
                ->toBase()
                ->map(fn ($record) => $record->attendance_date->format('Y-m-d'))
                ->unique()
                ->sort()
                ->values();

            $recordsByStudent = $monthRecords->groupBy('student_id');

            $recapRows = $members->map(function ($member) use ($recordsByStudent, $meetingDates) {
                $studentRecords = ($recordsByStudent->get($member->student_id) ?? collect())
                    ->keyBy(fn ($record) => $record->attendance_date->format('Y-m-d'));

                $perMeeting = $meetingDates->map(function ($meetingDate) use ($studentRecords) {
                    $record = $studentRecords->get($meetingDate);

                    return [
                        'date' => $meetingDate,
                        'raw' => $record?->status,
                        'letter' => match ($record?->status) {
                            'hadir' => 'H',
                            'izin' => 'I',
                            'sakit' => 'S',
                            'alpa' => 'A',
                            'belum_setor' => 'B',
                            'belum_siap' => 'N',
                            default => '–',
                        },
                    ];
                });

                $hadir = $studentRecords->where('status', 'hadir')->count();
                $totalMeetings = $meetingDates->count();

                return (object) [
                    'student' => $member->student,
                    'per_meeting' => $perMeeting,
                    'hadir' => $hadir,
                    'izin' => $studentRecords->where('status', 'izin')->count(),
                    'sakit' => $studentRecords->where('status', 'sakit')->count(),
                    'alpa' => $studentRecords->where('status', 'alpa')->count(),
                    'belum_setor' => $studentRecords->where('status', 'belum_setor')->count(),
                    'belum_siap' => $studentRecords->where('status', 'belum_siap')->count(),
                    'total_meetings' => $totalMeetings,
                    'percentage' => $totalMeetings > 0 ? round(($hadir / $totalMeetings) * 100, 1) : 0,
                ];
            });

            $recapTotals = [
                'hadir' => $recapRows->sum('hadir'),
                'izin' => $recapRows->sum('izin'),
                'sakit' => $recapRows->sum('sakit'),
                'alpa' => $recapRows->sum('alpa'),
                'belum_setor' => $recapRows->sum('belum_setor'),
                'belum_siap' => $recapRows->sum('belum_siap'),
                'meetings' => $meetingDates->count(),
            ];
        }

        return view('tahfidz.attendance.index', compact(
            'userId', 'groups', 'group', 'date', 'dateString', 'sessionType', 'month',
            'tab', 'members', 'attendance', 'presence', 'schedule', 'holiday', 'stats', 'history',
            'monthOptions', 'meetingDates', 'recapRows', 'recapTotals', 'canInput', 'academicYear'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'tahfidz_group_id' => 'required|exists:tahfidz_groups,id',
            'attendance_date' => 'required|date',
            'session_type' => 'required|in:setoran,murajaah,tasmi,evaluasi,tikror',
            'statuses' => 'required|array',
            'statuses.*' => 'required|in:hadir,izin,sakit,alpa,belum_setor,belum_siap',
            'notes' => 'nullable|array',
            'notes.*' => 'nullable|string|max:1000',
        ]);

        $group = TahfidzGroup::findOrFail($validated['tahfidz_group_id']);
        abort_unless($this->access->canInputSetoran($request->user(), $group), 403, 'Anda tidak berwenang mengisi absensi halaqah ini.');

        $memberIds = TahfidzGroupMember::where('tahfidz_group_id', $group->id)
            ->where('status', 'aktif')
            ->pluck('student_id')
            ->all();

        DB::transaction(function () use ($validated, $group, $memberIds, $request) {
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
        });

        if ($request->expectsJson()) {
            return response()->json([
                'saved' => true,
                'message' => 'Absensi tersimpan.',
                'saved_at' => now()->format('H:i'),
            ]);
        }

        return redirect()
            ->route('user.tahfidz.attendance.index', [
                'userId' => $userId,
                'group_id' => $group->id,
                'date' => $validated['attendance_date'],
                'session_type' => $validated['session_type'],
                'tab' => 'input',
            ])
            ->with('success', 'Absensi halaqah tersimpan.');
    }

    private function accessibleGroups(Request $request, ?string $academicYearId)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?: $user?->employment?->school_id;
        $query = TahfidzGroup::with(['school:id,name', 'studyGroup:id,name'])
            ->where('is_active', true)
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
