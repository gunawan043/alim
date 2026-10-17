<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\User;
use App\Services\KurikulumAccess;
use App\Services\SubjectGroupResolver;
use App\Services\TeacherRosterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Increment 3 — Master Rumpun Mata Pelajaran & Penetapan Koordinator.
 *
 * Otoritas rumpun = master data (`subjects.subject_group_id`);
 * koordinator ditetapkan melalui tugas tambahan per satuan pendidikan
 * (`other_teacher_tasks`). Koordinator TIDAK mendapat approval final atau
 * akses penuh otomatis — review & approval tetap terpisah (kebijakan Tahap 1).
 */
class SubjectGroupController extends Controller
{
    public function __construct(private readonly SubjectGroupResolver $resolver) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $groups = $this->resolver->groups()->map(function (SubjectGroup $group) {
            return [
                'group' => $group,
                'subject_count' => Subject::where('subject_group_id', $group->id)->count(),
                'teacher_count' => count($this->resolver->teacherIdsForGroup($group)),
                'coordinators' => $this->resolver->coordinatorAssignmentsForGroup($group),
            ];
        });

        $unmappedCount = Subject::whereNull('subject_group_id')->count();
        $crossSubjectCount = $this->resolver->crossSchoolSubjectGroups(
            AcademicYear::where('is_active', true)->value('id')
        )->count();

        return view('kurikulum.rumpun.index', compact('userId', 'groups', 'unmappedCount', 'crossSubjectCount'));
    }

    public function show(Request $request, string $userId, string $groupId)
    {
        $this->authorizeView($request);

        $group = SubjectGroup::findOrFail($groupId);
        $schoolId = $request->attributes->get('schoolContextId');

        $mappedSubjects = Subject::query()
            ->where('subject_group_id', $group->id)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('school:id,name')
            ->orderBy('name')
            ->get();

        $availableSubjects = Subject::query()
            ->where(function ($q) use ($group) {
                $q->whereNull('subject_group_id')->orWhere('subject_group_id', '<>', $group->id);
            })
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('school:id,name')
            ->orderBy('name')
            ->get();

        $memberIds = $this->resolver->teacherIdsForGroup($group);
        $members = User::whereIn('id', $memberIds)
            ->with(['employment:id,user_id,school_id', 'employment.school:id,name'])
            ->orderBy('name')
            ->get();

        $coordinators = $this->resolver->coordinatorAssignmentsForGroup($group, $schoolId);
        $activeYear = AcademicYear::where('is_active', true)->first();

        // Kandidat koordinator: guru pada satuan pendidikan konteks.
        $candidates = collect();
        if ($schoolId) {
            $candidateIds = app(TeacherRosterService::class)->idsForSchool($schoolId);
            $candidates = User::whereIn('id', $candidateIds)->orderBy('name')->get(['id', 'name']);
        }

        return view('kurikulum.rumpun.show', compact(
            'userId', 'group', 'mappedSubjects', 'availableSubjects',
            'members', 'coordinators', 'activeYear', 'schoolId', 'candidates'
        ));
    }

    public function crossSchool(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $crossGroups = $this->resolver->crossSchoolSubjectGroups(
            AcademicYear::where('is_active', true)->value('id')
        );

        return view('kurikulum.rumpun.cross-school', compact('userId', 'crossGroups'));
    }

    public function attachSubjects(Request $request, string $userId, string $groupId)
    {
        $this->authorizeManagement($request);

        $group = SubjectGroup::findOrFail($groupId);
        $data = $request->validate([
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        $schoolId = $request->attributes->get('schoolContextId');

        $updated = Subject::query()
            ->whereIn('id', $data['subject_ids'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->update(['subject_group_id' => $group->id]);

        return back()->with('success', "{$updated} mata pelajaran dipetakan ke rumpun {$group->name}.");
    }

    public function detachSubject(Request $request, string $userId, string $groupId, string $subjectId)
    {
        $this->authorizeManagement($request);

        $group = SubjectGroup::findOrFail($groupId);
        $subject = Subject::where('subject_group_id', $group->id)->findOrFail($subjectId);

        $this->ensureSchoolScope($request, $subject->school_id);

        $subject->forceFill(['subject_group_id' => null])->saveQuietly();

        return back()->with('success', "Mapel {$subject->name} dikeluarkan dari rumpun {$group->name}.");
    }

    public function assignCoordinator(Request $request, string $userId, string $groupId)
    {
        $this->authorizeManagement($request);

        $group = SubjectGroup::findOrFail($groupId);
        $schoolId = $request->attributes->get('schoolContextId');

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        if (! $schoolId) {
            return back()->with('error', 'Penetapan koordinator memerlukan konteks satuan pendidikan.');
        }

        if (! $group->coordinator_task_name) {
            return back()->with('error', 'Rumpun ini belum memiliki nama tugas koordinator resmi.');
        }

        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return back()->with('error', 'Tidak ada tahun ajaran aktif.');
        }

        DB::transaction(function () use ($group, $schoolId, $data, $activeYear) {
            // Kebijakan: SATU koordinator aktif per rumpun + satuan pendidikan +
            // tahun ajaran. Penetapan baru menggantikan koordinator sebelumnya.
            OtherTeacherTask::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $activeYear->id)
                ->whereIn('task_name', $this->resolver->coordinatorTaskNames($group))
                ->where('is_active', true)
                ->update(['is_active' => false]);

            OtherTeacherTask::create([
                'teacher_id' => $data['user_id'],
                'school_id' => $schoolId,
                'academic_year_id' => $activeYear->id,
                'task_name' => $group->coordinator_task_name,
                'task_code' => $group->code,
                'weekly_hours' => 0,
                'notes' => 'Ditetapkan dari Master Rumpun ('.$group->name.').',
                'is_active' => true,
            ]);
        });

        $this->rebuildSnapshotsFor($data['user_id']);

        return back()->with('success', 'Koordinator rumpun ditetapkan melalui tugas tambahan.');
    }

    public function deactivateCoordinator(Request $request, string $userId, string $groupId, string $taskId)
    {
        $this->authorizeManagement($request);

        $group = SubjectGroup::findOrFail($groupId);
        $task = OtherTeacherTask::findOrFail($taskId);

        $this->ensureSchoolScope($request, $task->school_id);

        if (! in_array($task->task_name, $this->resolver->coordinatorTaskNames($group), true)) {
            return back()->with('error', 'Tugas tambahan ini bukan koordinator rumpun tersebut.');
        }

        $task->update(['is_active' => false]);

        $this->rebuildSnapshotsFor($task->teacher_id);

        return back()->with('success', 'Penetapan koordinator dinonaktifkan (tugas tambahan tetap tercatat).');
    }

    // ─────────────────────────────────────────────────────────────

    private function authorizeView(Request $request): void
    {
        $user = $request->user();

        if (! app(KurikulumAccess::class)->isKurikulumTeam($user)) {
            abort(403, 'Hanya tim kurikulum yang dapat melihat master rumpun.');
        }
    }

    private function authorizeManagement(Request $request): void
    {
        $user = $request->user();

        if (! app(KurikulumAccess::class)->isKurikulumEditor($user)) {
            abort(403, 'Hanya tim kurikulum yang dapat mengelola master rumpun.');
        }
    }

    private function ensureSchoolScope(Request $request, ?string $schoolId): void
    {
        $context = $request->attributes->get('schoolContextId');

        if ($context && $schoolId && (string) $context !== (string) $schoolId) {
            abort(404, 'Data tidak ditemukan pada satuan pendidikan Anda.');
        }
    }

    private function rebuildSnapshotsFor(string $userId): void
    {
        try {
            \App\Authorization\Jobs\BuildSnapshotJob::dispatch($userId)->afterCommit();
        } catch (\Throwable) {
            // Snapshot akan dibangun saat cold-start berikutnya.
        }
    }
}
