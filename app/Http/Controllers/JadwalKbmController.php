<?php

namespace App\Http\Controllers;

use App\Http\Requests\JadwalKbmGenerateRequest;
use App\Http\Requests\JadwalKbmUpdateRequest;
use App\Models\AcademicYear;
use App\Models\JadwalKbm;
use App\Models\OtherTeacherTask;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\JadwalGeneratorService;
use App\Services\TeacherRosterService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class JadwalKbmController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $schoolId = $request->attributes->get('schoolContextId');
        $activeAy = AcademicYear::where('is_active', true)->first();

        $studyGroups = StudyGroup::with(['gradeLevel', 'homeroomTeacher'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get();

        $jadwals = JadwalKbm::with('studyGroup.gradeLevel', 'teacher')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get()
            ->groupBy('study_group_id');

        // Filter cepat: sudah / belum terjadwal.
        $status = $request->input('status');
        if ($status === 'terjadwal') {
            $studyGroups = $studyGroups->filter(fn ($sg) => ($jadwals[$sg->id] ?? collect())->isNotEmpty())->values();
        } elseif ($status === 'belum') {
            $studyGroups = $studyGroups->filter(fn ($sg) => ($jadwals[$sg->id] ?? collect())->isEmpty())->values();
        }

        $stats = [
            'rombel' => $studyGroups->count(),
            'terjadwal' => $studyGroups->filter(fn ($sg) => ($jadwals[$sg->id] ?? collect())->isNotEmpty())->count(),
            'slot' => (int) $studyGroups->sum(fn ($sg) => ($jadwals[$sg->id] ?? collect())->count()),
            'guru' => $studyGroups->flatMap(fn ($sg) => ($jadwals[$sg->id] ?? collect())->pluck('teacher_id'))->filter()->unique()->count(),
        ];

        return view('jadwal-kbm.index', compact('studyGroups', 'jadwals', 'activeAy', 'stats', 'status'));
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

        // Ringkasan assignment (SK guru) per rombel — sumber JP yang akan digenerate.
        $assignmentSummary = collect();
        if ($activeAy && $studyGroups->isNotEmpty()) {
            $assignmentSummary = TeachingAssignment::query()
                ->where('academic_year_id', $activeAy->id)
                ->where('status', 'active')
                ->whereIn('study_group_id', $studyGroups->pluck('id'))
                ->selectRaw('study_group_id, COUNT(*) as total_subjects, COALESCE(SUM(weekly_hours), 0) as total_hours')
                ->groupBy('study_group_id')
                ->get()
                ->keyBy('study_group_id');
        }

        // Tugas mengajar tambahan (data pendukung, bukan sumber JP generator).
        $otherTasks = OtherTeacherTask::with(['teacher:id,name', 'studyGroup:id,name'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->where('is_active', true)
            ->orderBy('task_name')
            ->get();

        $masterSlotCount = $schoolId
            ? DB::table('class_schedule_slots')->where('school_id', $schoolId)->where('is_active', 1)->count()
            : 0;

        return view('jadwal-kbm.generate', compact(
            'studyGroups',
            'activeAy',
            'assignmentSummary',
            'otherTasks',
            'masterSlotCount'
        ));
    }

    public function generate(JadwalKbmGenerateRequest $request, string $userId, JadwalGeneratorService $generator)
    {
        $data = $request->validated();

        $results = $generator->generateBulk(
            $data['study_group_ids'],
            $data['academic_year_id'],
            $data['semester'],
            (bool) ($data['overwrite'] ?? false)
        );

        $report = $this->buildReport($results);

        $redirect = redirect()
            ->route('user.jadwal-kbm.index', ['userId' => $userId])
            ->with('shortage_report', $report)
            ->with('success', "Generate selesai: {$report['total_generated']} slot dari {$report['total_requested']} JP.");

        if ($report['total_missing'] > 0) {
            $redirect->with('warning', "{$report['total_missing']} JP belum mendapatkan slot — lihat laporan kekurangan JP.");
        }

        return $redirect;
    }

    /**
     * Generate per rombel (dipertahankan dari fitur lama).
     */
    public function generateSingle(Request $request, string $userId, string $studyGroupId, JadwalGeneratorService $generator)
    {
        $this->authorizeGenerate($request);

        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $activeAy = AcademicYear::where('is_active', true)->first();
        abort_unless($activeAy, 422, 'Tahun ajaran aktif tidak ditemukan.');

        $semester = $request->input('semester', $activeAy->semester ?? 'ganjil');
        $overwrite = $request->boolean('overwrite', true);

        $result = $generator->generateForStudyGroup($studyGroup->id, $activeAy->id, $semester, $overwrite);
        $report = $this->buildReport(collect([$result]));

        $redirect = redirect()
            ->route('user.jadwal-kbm.show', ['userId' => $userId, 'studyGroupId' => $studyGroupId])
            ->with('shortage_report', $report)
            ->with('success', "Generate {$studyGroup->full_name}: {$result['generated']} slot dari {$result['requested']} JP.");

        if (! empty($result['shortages'])) {
            $redirect->with('warning', 'Sebagian JP belum mendapatkan slot — lihat laporan kekurangan JP.');
        }

        return $redirect;
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

        $days = $this->days();

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

        $teacherIds = app(TeacherRosterService::class)->idsForSchool($schoolId);
        $teachers = User::whereIn('id', $teacherIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $subjects = Subject::where(function ($q) use ($schoolId) {
            $q->whereNull('school_id');
            if ($schoolId) {
                $q->orWhere('school_id', $schoolId);
            }
        })->orderBy('name')->get(['id', 'name', 'code']);

        $days = $this->days();
        $maxSlots = JadwalGeneratorService::MAX_PERIODS_PER_DAY;

        return view('jadwal-kbm.edit', compact('studyGroup', 'jadwals', 'teachers', 'subjects', 'days', 'maxSlots', 'activeAy'));
    }

    public function update(JadwalKbmUpdateRequest $request, string $userId, string $studyGroupId)
    {
        $data = $request->validated();
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $generator = app(JadwalGeneratorService::class);
        $conflicts = [];

        DB::transaction(function () use ($data, $studyGroup, $generator, &$conflicts) {
            foreach ($data['entries'] as $entry) {
                $jadwal = JadwalKbm::where('study_group_id', $studyGroup->id)
                    ->where('id', $entry['id'])
                    ->firstOrFail();

                $day = (int) $entry['day_of_week'];
                $slot = (int) $entry['slot_index'];

                // Validasi slot terhadap master slot sekolah (termasuk is_break).
                if (! $generator->isTeachingSlot($studyGroup->school_id, $day, $slot)) {
                    $conflicts[] = [
                        'jadwal_id' => $jadwal->id,
                        'reason' => 'Slot tidak tersedia (jam istirahat atau di luar master slot sekolah)',
                    ];

                    continue;
                }

                $teacherConflict = $entry['teacher_id']
                    ? JadwalKbm::where('teacher_id', $entry['teacher_id'])
                        ->where('day_of_week', $day)
                        ->where('slot_index', $slot)
                        ->where('academic_year_id', $jadwal->academic_year_id)
                        ->where('is_active', true)
                        ->where('id', '!=', $jadwal->id)
                        ->exists()
                    : false;

                $sgConflict = JadwalKbm::where('study_group_id', $studyGroup->id)
                    ->where('day_of_week', $day)
                    ->where('slot_index', $slot)
                    ->where('academic_year_id', $jadwal->academic_year_id)
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

                $times = $generator->resolveSlotTimesPublic($slot, $day, $studyGroup->school_id);

                $jadwal->update([
                    'day_of_week' => $day,
                    'slot_index' => $slot,
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
            ->route('user.jadwal-kbm.show', ['userId' => $userId, 'studyGroupId' => $studyGroupId])
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

        $days = $this->days();

        return view('jadwal-kbm.cetak', compact('studyGroup', 'jadwals', 'days', 'activeAy'));
    }

    public function forTeacher(Request $request, string $userId, string $teacherId)
    {
        $this->authorizeView($request);

        $schoolId = $request->attributes->get('schoolContextId');
        $activeAy = AcademicYear::where('is_active', true)->first();

        $authUser = auth()->user();
        if ($authUser && $authUser->id !== $teacherId && ! canPermission('jadwalkbm.publish') && ! canPermission('jadwal-kbm-all-access')) {
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

        $days = $this->days();

        return view('jadwal-kbm.teacher', compact('teacher', 'jadwals', 'days', 'activeAy'));
    }

    /**
     * Susun laporan hasil generate (termasuk kekurangan JP dan sisa konflik).
     *
     * @param  Collection<int, array<string, mixed>>  $results
     * @return array<string, mixed>
     */
    private function buildReport(Collection $results): array
    {
        $groups = [];
        $totalGenerated = 0;
        $totalRequested = 0;
        $totalMissing = 0;
        $allConflicts = [];

        foreach ($results as $result) {
            $missing = (int) collect($result['shortages'] ?? [])->sum('missing');

            $groups[] = [
                'name' => $result['study_group_name'] ?? $result['study_group_id'] ?? '-',
                'generated' => (int) ($result['generated'] ?? 0),
                'requested' => (int) ($result['requested'] ?? 0),
                'missing' => $missing,
                'shortages' => $result['shortages'] ?? [],
                'conflicts' => $result['conflicts'] ?? [],
                'has_assignments' => (bool) ($result['has_assignments'] ?? true),
            ];

            $totalGenerated += (int) ($result['generated'] ?? 0);
            $totalRequested += (int) ($result['requested'] ?? 0);
            $totalMissing += $missing;

            foreach ($result['conflicts'] ?? [] as $conflict) {
                $allConflicts[] = ($result['study_group_name'] ?? '-').": {$conflict}";
            }
        }

        return [
            'groups' => $groups,
            'total_generated' => $totalGenerated,
            'total_requested' => $totalRequested,
            'total_missing' => $totalMissing,
            'conflicts' => $allConflicts,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function days(): array
    {
        return [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (
            canPermission('jadwalkbm.read')
            || canPermission('jadwal_kbm_view')
            || canPermission('jadwal_kbm_manage')
            || canPermission('jadwal-kbm-all-access')
        ), 403, 'Anda tidak memiliki akses ke jadwal pelajaran.');
    }

    private function authorizeGenerate(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (
            canPermission('jadwalkbm.write')
            || canPermission('jadwalkbm.publish')
            || canPermission('jadwal_kbm_generate')
            || canPermission('jadwal_kbm_manage')
            || canPermission('jadwal-kbm-generate-all-access')
        ), 403, 'Anda tidak memiliki akses untuk generate jadwal.');
    }

    private function authorizeUpdate(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (
            canPermission('jadwalkbm.write')
            || canPermission('jadwal_kbm_update')
            || canPermission('jadwal_kbm_manage')
            || canPermission('jadwal-kbm-update-all-access')
        ), 403, 'Anda tidak memiliki akses untuk mengubah jadwal.');
    }
}
