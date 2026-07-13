<?php

namespace App\Http\Controllers;

use App\Http\Requests\JadwalKbmGenerateRequest;
use App\Http\Requests\JadwalKbmUpdateRequest;
use App\Models\AcademicYear;
use App\Models\JadwalKbm;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use App\Services\JadwalGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JadwalKbmController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $schoolId = $request->attributes->get('schoolContextId');
        $activeAy = AcademicYear::where('is_active', true)->first();

        $jadwals = JadwalKbm::with('studyGroup.gradeLevel', 'teacher')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get()
            ->groupBy('study_group_id');

        return view('jadwal-kbm.index', compact('jadwals', 'activeAy'));
    }

    public function generateIndex(Request $request, string $userId)
    {
        $this->authorizeGenerate($request);

        $schoolId = $request->attributes->get('schoolContextId');
        $activeAy = AcademicYear::where('is_active', true)->first();

        $studyGroups = StudyGroup::with('gradeLevel')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get();

        return view('jadwal-kbm.generate', compact('studyGroups', 'activeAy'));
    }

    public function generate(JadwalKbmGenerateRequest $request, string $userId, JadwalGeneratorService $generator)
    {
        $data = $request->validated();
        $schoolId = $request->attributes->get('schoolContextId');

        if ($data['overwrite'] ?? false) {
            JadwalKbm::where('school_id', $schoolId)
                ->where('academic_year_id', $data['academic_year_id'])
                ->whereIn('study_group_id', $data['study_group_ids'])
                ->delete();
        }

        $results = $generator->generateBulk(
            $data['study_group_ids'],
            $data['academic_year_id'],
            $data['semester']
        );

        $total = $results->sum('generated');
        $failed = $results->where('generated', 0)->values();

        return redirect()
            ->route('jadwal-kbm.index')
            ->with('success', "Berhasil generate {$total} slot jadwal".($failed->count() ? " ({$failed->count()} rombel gagal)" : ''));
    }

    public function show(Request $request, string $userId, string $studyGroupId)
    {
        $this->authorizeView($request);

        $schoolId = $request->attributes->get('schoolContextId');
        $activeAy = AcademicYear::where('is_active', true)->first();

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $jadwals = JadwalKbm::with(['teacher', 'subject'])
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $activeAy?->id)
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get()
            ->groupBy('day_of_week');

        $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

        return view('jadwal-kbm.show', compact('studyGroup', 'jadwals', 'days', 'activeAy'));
    }

    public function edit(Request $request, string $userId, string $studyGroupId)
    {
        $this->authorizeUpdate($request);

        $schoolId = $request->attributes->get('schoolContextId');
        $activeAy = AcademicYear::where('is_active', true)->first();

        $studyGroup = StudyGroup::with('gradeLevel')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $jadwals = JadwalKbm::with(['teacher', 'subject'])
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $activeAy?->id)
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get();

        $teacherIds = usersHavingPermission('general_tutor.readable');
        $teachers = User::where('school_id', $schoolId)
            ->whereIn('id', $teacherIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $subjects = Subject::where('school_id', $schoolId)
            ->orWhereNull('school_id')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
        $maxSlots = JadwalGeneratorService::MAX_PERIODS_PER_DAY;

        return view('jadwal-kbm.edit', compact('studyGroup', 'jadwals', 'teachers', 'subjects', 'days', 'maxSlots', 'activeAy'));
    }

    public function update(JadwalKbmUpdateRequest $request, string $userId, string $studyGroupId)
    {
        $data = $request->validated();
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $conflicts = [];

        DB::transaction(function () use ($data, $studyGroup, &$conflicts) {
            foreach ($data['entries'] as $entry) {
                $jadwal = JadwalKbm::where('study_group_id', $studyGroup->id)
                    ->where('id', $entry['id'])
                    ->firstOrFail();

                $teacherConflict = $entry['teacher_id']
                    ? JadwalKbm::where('teacher_id', $entry['teacher_id'])
                        ->where('day_of_week', $entry['day_of_week'])
                        ->where('slot_index', $entry['slot_index'])
                        ->where('academic_year_id', $jadwal->academic_year_id)
                        ->where('is_active', true)
                        ->where('id', '!=', $jadwal->id)
                        ->exists()
                    : false;

                $sgConflict = JadwalKbm::where('study_group_id', $studyGroup->id)
                    ->where('day_of_week', $entry['day_of_week'])
                    ->where('slot_index', $entry['slot_index'])
                    ->where('is_active', true)
                    ->where('id', '!=', $jadwal->id)
                    ->exists();

                if ($teacherConflict || $sgConflict) {
                    $conflicts[] = [
                        'jadwal_id' => $jadwal->id,
                        'reason' => $teacherConflict
                            ? 'Guru sudah mengajar di slot ini'
                            : 'Rombel sudah ada pelajaran di slot ini',
                    ];

                    continue;
                }

                $times = app(JadwalGeneratorService::class)
                    ->resolveSlotTimesPublic($entry['slot_index'], $entry['day_of_week']);

                $jadwal->update([
                    'day_of_week' => $entry['day_of_week'],
                    'slot_index' => $entry['slot_index'],
                    'start_time' => $times['start'],
                    'end_time' => $times['end'],
                    'teacher_id' => $entry['teacher_id'] ?? null,
                    'subject_id' => $entry['subject_id'],
                    'room' => $entry['room'] ?? $jadwal->room,
                ]);
            }
        });

        if (! empty($conflicts)) {
            return back()
                ->withInput()
                ->with('conflict_warnings', $conflicts)
                ->with('warning', count($conflicts).' entri dilewati karena konflik jadwal');
        }

        return redirect()
            ->route('jadwal-kbm.show', ['userId' => $userId, 'studyGroupId' => $studyGroupId])
            ->with('success', 'Jadwal berhasil diperbarui');
    }

    public function cetak(Request $request, string $userId, string $studyGroupId)
    {
        $this->authorizeView($request);

        $schoolId = $request->attributes->get('schoolContextId');
        $activeAy = AcademicYear::where('is_active', true)->first();

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $jadwals = JadwalKbm::with(['teacher', 'subject'])
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $activeAy?->id)
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get()
            ->groupBy('day_of_week');

        $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

        return view('jadwal-kbm.cetak', compact('studyGroup', 'jadwals', 'days', 'activeAy'));
    }

    public function forTeacher(Request $request, string $userId, string $teacherId)
    {
        $this->authorizeView($request);

        $schoolId = $request->attributes->get('schoolContextId');
        $activeAy = AcademicYear::where('is_active', true)->first();

        $authUser = auth()->user();
        if ($authUser && $authUser->id !== $teacherId) {
            abort(403, 'Anda hanya dapat melihat jadwal mengajar sendiri.');
        }

        $teacher = User::findOrFail($teacherId);

        $jadwals = JadwalKbm::with(['studyGroup.gradeLevel', 'subject'])
            ->where('teacher_id', $teacherId)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get()
            ->groupBy('day_of_week');

        $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

        return view('jadwal-kbm.teacher', compact('teacher', 'jadwals', 'days', 'activeAy'));
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (
            canPermission('jadwal_kbm_view')
            || canPermission('jadwal_kbm_manage')
            || canPermission('jadwal-kbm-all-access')
        ), 403, 'Anda tidak memiliki akses ke jadwal pelajaran.');
    }

    private function authorizeGenerate(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (
            canPermission('jadwal_kbm_generate')
            || canPermission('jadwal_kbm_manage')
            || canPermission('jadwal-kbm-generate-all-access')
        ), 403, 'Anda tidak memiliki akses untuk generate jadwal.');
    }

    private function authorizeUpdate(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (
            canPermission('jadwal_kbm_update')
            || canPermission('jadwal_kbm_manage')
            || canPermission('jadwal-kbm-update-all-access')
        ), 403, 'Anda tidak memiliki akses untuk mengubah jadwal.');
    }
}
