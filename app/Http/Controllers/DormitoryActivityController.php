<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\StudyGroup;
use App\Models\StudentClassHistory;
use App\Models\Dormitory;
use App\Models\DormitoryActivity;
use App\Models\DormitoryActivityAttendance;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryStaffAssignment;
use App\Models\RoomSupervisor;
use App\Services\Dormitory\DormitoryActivityGenerator;
use App\Support\UserJabatan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Presensi kegiatan asrama.
 *
 * - Kepala/Wakil Asrama: membuat, menutup, dan membuka kegiatan.
 * - Staf Asrama & Wali Kamar: mengisi presensi kehadiran santri.
 */
class DormitoryActivityController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $this->authorizeView($request->user(), $dormitory);

        $query = DormitoryActivity::with('creator')
            ->where('dormitory_id', $asramaUuid)
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->q.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('activity_date', $request->date))
            ->withCount([
                'attendances as hadir_count' => fn ($q) => $q->where('status', 'hadir'),
                'attendances as alpa_count' => fn ($q) => $q->where('status', 'alpa'),
                'attendances as total_count',
            ])
            ->orderByDesc('activity_date')
            ->orderByDesc('created_at');

        $activities = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => DormitoryActivity::where('dormitory_id', $asramaUuid)->count(),
            'open' => DormitoryActivity::where('dormitory_id', $asramaUuid)->where('status', 'open')->count(),
            'closed' => DormitoryActivity::where('dormitory_id', $asramaUuid)->where('status', 'closed')->count(),
            'attendance' => DormitoryActivityAttendance::whereHas('activity', fn ($q) => $q->where('dormitory_id', $asramaUuid))->count(),
        ];

        return view('dormitory.activities.index', [
            'dormitory' => $dormitory,
            'userId' => $userId,
            'activities' => $activities,
            'stats' => $stats,
            'canManage' => $this->canManage($request->user(), $dormitory),
            'canRecord' => $this->canRecord($request->user(), $dormitory),
        ]);
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat menambah kegiatan.');

        return view('dormitory.activities.create', compact('dormitory', 'userId'));
    }

    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat menambah kegiatan.');

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'activity_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'location' => 'nullable|string|max:191',
            'description' => 'nullable|string|max:2000',
        ]);

        DormitoryActivity::create($validated + [
            'dormitory_id' => $asramaUuid,
            'academic_year_id' => AcademicYear::where('is_active', true)->value('id'),
            'status' => 'open',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('user.asrama.kegiatan.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Kegiatan berhasil dibuat. Staf dan wali kamar dapat mengisi presensi.');
    }

    public function schedules(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat mengatur kegiatan rutin.');

        $schedules = \App\Models\DormitoryActivitySchedule::with('creator')
            ->where('dormitory_id', $asramaUuid)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $activeYear = AcademicYear::where('is_active', true)->first();
        $studyGroups = StudyGroup::where('school_id', $dormitory->school_id)
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->orderBy('name')->get();

        return view('dormitory.activities.schedules', compact(
            'dormitory', 'userId', 'schedules', 'studyGroups'
        ));
    }

    public function scheduleStore(Request $request, string $userId, string $asramaUuid, DormitoryActivityGenerator $generator)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat mengatur kegiatan rutin.');

        $validated = $this->validateSchedule($request, $asramaUuid);

        $schedule = \App\Models\DormitoryActivitySchedule::create($validated + [
            'dormitory_id' => $asramaUuid,
            'created_by' => $request->user()->id,
        ]);

        $created = $generator->generateRange(now()->startOfDay(), 14, $asramaUuid);

        return back()->with('success', 'Kegiatan rutin disimpan. '.$created.' sesi presensi dibuat untuk 14 hari ke depan.');
    }

    public function scheduleUpdate(Request $request, string $userId, string $asramaUuid, string $scheduleId)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat mengatur kegiatan rutin.');

        $schedule = \App\Models\DormitoryActivitySchedule::where('dormitory_id', $asramaUuid)->findOrFail($scheduleId);
        $schedule->update($this->validateSchedule($request, $asramaUuid));

        return back()->with('success', 'Kegiatan rutin diperbarui. Sesi berikutnya akan mengikuti jadwal baru.');
    }

    public function scheduleDestroy(Request $request, string $userId, string $asramaUuid, string $scheduleId)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat menghapus kegiatan rutin.');

        \App\Models\DormitoryActivitySchedule::where('dormitory_id', $asramaUuid)->findOrFail($scheduleId)->delete();

        return back()->with('success', 'Kegiatan rutin dihapus. Sesi yang sudah terbentuk tetap tersimpan.');
    }

    public function scheduleGenerate(Request $request, string $userId, string $asramaUuid, DormitoryActivityGenerator $generator)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat membuat sesi kegiatan.');

        $created = $generator->generateRange(now()->startOfDay(), 30, $asramaUuid);

        return back()->with('success', $created.' sesi kegiatan berhasil dibuat untuk 30 hari ke depan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSchedule(Request $request, string $asramaUuid): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'recurrence' => 'required|in:daily,weekly',
            'days_of_week' => 'required_if:recurrence,weekly|array',
            'days_of_week.*' => 'integer|between:1,7',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'location' => 'nullable|string|max:191',
            'description' => 'nullable|string|max:2000',
            'target_type' => 'required|in:all,class',
            'target_study_group_ids' => 'required_if:target_type,class|array',
            'target_study_group_ids.*' => 'exists:study_groups,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['days_of_week'] = $validated['recurrence'] === 'weekly'
            ? array_values(array_map('intval', $validated['days_of_week'] ?? []))
            : null;
        $validated['target_study_group_ids'] = $validated['target_type'] === 'class'
            ? array_values($validated['target_study_group_ids'] ?? [])
            : null;
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    public function show(Request $request, string $userId, string $asramaUuid, string $activityId)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $this->authorizeView($request->user(), $dormitory);

        $activity = DormitoryActivity::with(['creator'])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($activityId);

        $attendances = $activity->attendances()
            ->with(['student', 'room', 'recorder'])
            ->orderBy('room_id')
            ->get();

        $summary = [
            'hadir' => $attendances->where('status', 'hadir')->count(),
            'izin' => $attendances->where('status', 'izin')->count(),
            'sakit' => $attendances->where('status', 'sakit')->count(),
            'alpa' => $attendances->where('status', 'alpa')->count(),
            'total' => $attendances->count(),
        ];

        return view('dormitory.activities.show', [
            'dormitory' => $dormitory,
            'userId' => $userId,
            'activity' => $activity,
            'attendances' => $attendances,
            'summary' => $summary,
            'canManage' => $this->canManage($request->user(), $dormitory),
            'canRecord' => $this->canRecord($request->user(), $dormitory),
        ]);
    }

    public function attendance(Request $request, string $userId, string $asramaUuid, string $activityId)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canRecord($request->user(), $dormitory), 403, 'Hanya staf asrama atau wali kamar yang dapat mengisi presensi kegiatan.');

        $activity = DormitoryActivity::where('dormitory_id', $asramaUuid)->findOrFail($activityId);
        abort_unless($activity->isOpen(), 403, 'Kegiatan sudah ditutup. Presensi tidak dapat diubah.');

        $activeYear = AcademicYear::where('is_active', true)->first();
        $residentsQuery = DormitoryResident::with(['student', 'room'])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id)
            ->where('is_active', true);

        // Target kelas (opsional): hanya santri di kelas yang dipilih.
        if ($activity->target_type === 'class' && ! empty($activity->target_study_group_ids)) {
            $studentIds = StudentClassHistory::whereIn('study_group_id', (array) $activity->target_study_group_ids)
                ->where('academic_year_id', $activeYear?->id)
                ->where('is_active', true)
                ->pluck('student_id');

            $residentsQuery->whereIn('student_id', $studentIds);
        }

        $residents = $residentsQuery->orderBy('room_id')->get();

        $existing = $activity->attendances()->get()->keyBy('resident_id');

        return view('dormitory.activities.attendance', [
            'dormitory' => $dormitory,
            'userId' => $userId,
            'activity' => $activity,
            'residentsByRoom' => $residents->groupBy(fn ($r) => $r->room?->name ?? 'Tanpa Kamar'),
            'existing' => $existing,
            'existingCount' => $existing->count(),
        ]);
    }

    public function attendanceStore(Request $request, string $userId, string $asramaUuid, string $activityId)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canRecord($request->user(), $dormitory), 403, 'Hanya staf asrama atau wali kamar yang dapat mengisi presensi kegiatan.');

        $activity = DormitoryActivity::where('dormitory_id', $asramaUuid)->findOrFail($activityId);
        abort_unless($activity->isOpen(), 403, 'Kegiatan sudah ditutup. Presensi tidak dapat diubah.');

        $data = $request->validate([
            'attendances' => 'required|array|min:1',
            'attendances.*.selected' => 'nullable|boolean',
            'attendances.*.status' => 'nullable|in:hadir,izin,sakit,alpa',
            'attendances.*.notes' => 'nullable|string|max:500',
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();
        $residents = DormitoryResident::where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $created = 0;
        $updated = 0;
        $alpaStudentIds = [];

        DB::transaction(function () use ($data, $activity, $residents, $request, &$created, &$updated, &$alpaStudentIds) {
            foreach ($data['attendances'] as $residentId => $item) {
                if (empty($item['selected']) || empty($item['status'])) {
                    continue;
                }

                $resident = $residents->get($residentId);
                if (! $resident) {
                    continue;
                }

                $record = DormitoryActivityAttendance::updateOrCreate(
                    ['activity_id' => $activity->id, 'resident_id' => $resident->id],
                    [
                        'student_id' => $resident->student_id,
                        'room_id' => $resident->room_id,
                        'status' => $item['status'],
                        'notes' => $item['notes'] ?? null,
                        'recorded_by' => $request->user()->id,
                        'recorded_at' => now(),
                    ],
                );

                if ($item['status'] === 'alpa') {
                    $alpaStudentIds[] = $resident->student_id;
                }

                $record->wasRecentlyCreated ? $created++ : $updated++;
            }
        });

        if ($alpaStudentIds !== []) {
            app(\App\Services\NotificationUniversalService::class)->notifyGuardiansForStudents(
                array_values(array_unique($alpaStudentIds)),
                [
                    'module' => 'dormitory',
                    'reference_type' => 'dormitory_activity',
                    'reference_id' => $activity->id,
                    'type' => 'warning',
                    'action' => 'activity_alpa',
                    'title' => 'Tidak hadir: '.$activity->name,
                    'message' => 'Santri tidak hadir pada kegiatan '.$activity->name.' ('.$activity->activity_date?->translatedFormat('d/m/Y').').',
                    'priority' => 'medium',
                ]
            );
        }

        return redirect()->route('user.asrama.kegiatan.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid, 'activityId' => $activity->id])
            ->with('success', "Presensi kegiatan disimpan ({$created} baru, {$updated} diperbarui).");
    }

    public function close(Request $request, string $userId, string $asramaUuid, string $activityId)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat menutup kegiatan.');

        DormitoryActivity::where('dormitory_id', $asramaUuid)->findOrFail($activityId)->update(['status' => 'closed']);

        return back()->with('success', 'Kegiatan ditutup. Presensi dikunci.');
    }

    public function reopen(Request $request, string $userId, string $asramaUuid, string $activityId)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat membuka kegiatan.');

        DormitoryActivity::where('dormitory_id', $asramaUuid)->findOrFail($activityId)->update(['status' => 'open']);

        return back()->with('success', 'Kegiatan dibuka kembali. Presensi dapat diisi.');
    }

    private function authorizeView($user, Dormitory $dormitory): void
    {
        abort_unless($user && ($this->canManage($user, $dormitory) || $this->canRecord($user, $dormitory)), 403, 'Anda tidak memiliki akses ke kegiatan asrama ini.');
    }

    private function canManage($user, Dormitory $dormitory): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin()) {
            return true;
        }

        if ((string) $dormitory->head_id === (string) $user->id) {
            return true;
        }

        if (canPermission('asrama-create')) {
            return true;
        }

        return UserJabatan::hasAny($user, [
            'kepala asrama', 'wakil kepala asrama', 'wakil asrama',
            'kepala asrama putra', 'kepala asrama putri',
            'wakil asrama putra', 'wakil asrama putri',
            'kepala asrama putera', 'wakil asrama putera',
        ]);
    }

    private function canRecord($user, Dormitory $dormitory): bool
    {
        if ($this->canManage($user, $dormitory)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if (DormitoryStaffAssignment::active()->forUser($user->id)->forDormitory($dormitory->id)->exists()) {
            return true;
        }

        return RoomSupervisor::active()
            ->where('user_id', $user->id)
            ->where('dormitory_id', $dormitory->id)
            ->exists();
    }
}
