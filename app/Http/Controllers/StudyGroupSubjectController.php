<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class StudyGroupSubjectController extends Controller
{
    /**
     * GET /api/study-groups/{id}/subjects
     * List subjects assigned to a rombel (used by AJAX on study-groups.show).
     */
    public function index(Request $request, string $studyGroupId): JsonResponse
    {
        $studyGroup = StudyGroup::findOrFail($studyGroupId);

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $assignments = StudyGroupSubject::with(['subject:id,code,name,category,credit_hours', 'teacher:id,name'])
            ->where('study_group_id', $studyGroupId)
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (StudyGroupSubject $a) {
                return [
                    'id' => $a->id,
                    'subject_id' => $a->subject_id,
                    'subject_code' => $a->subject?->code,
                    'subject_name' => $a->subject?->name,
                    'subject_category' => $a->subject?->category,
                    'credit_hours' => $a->subject?->credit_hours,
                    'teacher_id' => $a->teacher_id,
                    'teacher_name' => $a->teacher?->name,
                    'weekly_hours' => (float) $a->weekly_hours,
                    'is_active' => (bool) $a->is_active,
                    'notes' => $a->notes,
                ];
            });

        return response()->json([
            'study_group_id' => $studyGroupId,
            'total' => $assignments->count(),
            'assignments' => $assignments,
        ]);
    }

    /**
     * GET /study-groups/{id}/subjects/create
     * Form for assigning a new subject to a rombel.
     */
    public function create(Request $request, string $userId, string $studyGroupId)
    {
        $studyGroup = StudyGroup::findOrFail($studyGroupId);
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $academicYearId = $studyGroup->academic_year_id;
        $schoolId = $studyGroup->school_id;

        // Subjects available to assign = school subjects minus already-assigned active ones.
        $assignedSubjectIds = StudyGroupSubject::where('study_group_id', $studyGroupId)
            ->where('is_active', true)
            ->pluck('subject_id')
            ->all();

        $availableSubjects = Subject::where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereNotIn('id', $assignedSubjectIds)
            ->orderBy('name')
            ->get();

        $teachers = User::whereHas('employment', fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get();

        $academicYear = AcademicYear::find($academicYearId);

        return view('study-group-subjects.create', [
            'studyGroup' => $studyGroup,
            'availableSubjects' => $availableSubjects,
            'teachers' => $teachers,
            'academicYear' => $academicYear,
            'userId' => $userId,
        ]);
    }

    /**
     * POST /study-groups/{id}/subjects
     * Assign a subject to a rombel.
     *
     * Body: subject_id, teacher_id?, weekly_hours?, notes?
     */
    public function store(Request $request, string $userId, string $studyGroupId): RedirectResponse
    {
        $studyGroup = StudyGroup::findOrFail($studyGroupId);
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $data = $request->validate([
            'subject_id' => [
                'required',
                'uuid',
                Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $studyGroup->school_id)),
            ],
            'teacher_id' => ['nullable', 'uuid', 'exists:users,id'],
            'weekly_hours' => ['nullable', 'numeric', 'min:0.5', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $teacherId = $data['teacher_id'] ?? null;
        $weeklyHours = $data['weekly_hours'] ?? 2.0;

        // Idempotent: if assignment already exists (incl. soft-deleted), revive it.
        $existing = StudyGroupSubject::withTrashed()
            ->where('study_group_id', $studyGroupId)
            ->where('subject_id', $data['subject_id'])
            ->where('academic_year_id', $studyGroup->academic_year_id)
            ->first();

        if ($existing) {
            $existing->fill([
                'school_id' => $studyGroup->school_id,
                'teacher_id' => $teacherId,
                'weekly_hours' => $weeklyHours,
                'is_active' => true,
                'notes' => $data['notes'] ?? null,
            ]);
            $existing->deleted_at = null;
            $existing->save();
        } else {
            StudyGroupSubject::create([
                'school_id' => $studyGroup->school_id,
                'academic_year_id' => $studyGroup->academic_year_id,
                'study_group_id' => $studyGroupId,
                'subject_id' => $data['subject_id'],
                'teacher_id' => $teacherId,
                'weekly_hours' => $weeklyHours,
                'is_active' => true,
                'notes' => $data['notes'] ?? null,
            ]);
        }

        return redirect()
            ->route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroupId])
            ->with('success', 'Mata pelajaran berhasil di-assign ke rombel.');
    }

    /**
     * POST /study-groups/{id}/subjects/bulk
     * Bulk assign multiple subjects at once.
     *
     * Body: { subjects: [{ subject_id, teacher_id?, weekly_hours? }, ...] }
     */
    public function bulkStore(Request $request, string $userId, string $studyGroupId): RedirectResponse
    {
        $studyGroup = StudyGroup::findOrFail($studyGroupId);
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $data = $request->validate([
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*.subject_id' => [
                'required',
                'uuid',
                Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $studyGroup->school_id)),
            ],
            'subjects.*.teacher_id' => ['nullable', 'uuid', 'exists:users,id'],
            'subjects.*.weekly_hours' => ['nullable', 'numeric', 'min:0.5', 'max:40'],
        ]);

        $created = 0;
        $updated = 0;
        DB::beginTransaction();
        try {
            foreach ($data['subjects'] as $row) {
                $existing = StudyGroupSubject::withTrashed()
                    ->where('study_group_id', $studyGroupId)
                    ->where('subject_id', $row['subject_id'])
                    ->where('academic_year_id', $studyGroup->academic_year_id)
                    ->first();

                $payload = [
                    'school_id' => $studyGroup->school_id,
                    'teacher_id' => $row['teacher_id'] ?? null,
                    'weekly_hours' => $row['weekly_hours'] ?? 2.0,
                    'is_active' => true,
                ];

                if ($existing) {
                    $existing->fill($payload);
                    $existing->deleted_at = null;
                    $existing->save();
                    $updated++;
                } else {
                    StudyGroupSubject::create(array_merge($payload, [
                        'academic_year_id' => $studyGroup->academic_year_id,
                        'study_group_id' => $studyGroupId,
                        'subject_id' => $row['subject_id'],
                    ]));
                    $created++;
                }
            }
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('bulkStore study_group_subjects gagal', [
                'study_group_id' => $studyGroupId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal assign mapel: '.$e->getMessage());
        }

        return redirect()
            ->route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroupId])
            ->with('success', "Assign selesai: {$created} baru, {$updated} diperbarui.");
    }

    /**
     * PUT /study-groups/{id}/subjects/{assignmentId}
     * Update an existing assignment (e.g. change teacher, weekly_hours, is_active).
     */
    public function update(Request $request, string $userId, string $studyGroupId, string $assignmentId): RedirectResponse
    {
        $studyGroup = StudyGroup::findOrFail($studyGroupId);
        $assignment = StudyGroupSubject::where('id', $assignmentId)
            ->where('study_group_id', $studyGroupId)
            ->firstOrFail();

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $data = $request->validate([
            'teacher_id' => ['nullable', 'uuid', 'exists:users,id'],
            'weekly_hours' => ['nullable', 'numeric', 'min:0.5', 'max:40'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $assignment->fill(array_filter($data, fn ($v) => $v !== null));
        $assignment->save();

        return redirect()
            ->route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroupId])
            ->with('success', 'Assignment mapel diperbarui.');
    }

    /**
     * DELETE /study-groups/{id}/subjects/{assignmentId}
     * Unassign a subject from a rombel (soft delete + cascade teardown).
     */
    public function destroy(Request $request, string $userId, string $studyGroupId, string $assignmentId): RedirectResponse
    {
        $studyGroup = StudyGroup::findOrFail($studyGroupId);
        $assignment = StudyGroupSubject::where('id', $assignmentId)
            ->where('study_group_id', $studyGroupId)
            ->firstOrFail();

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $assignment->delete();

        return redirect()
            ->route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroupId])
            ->with('success', 'Mapel di-unassign dari rombel.');
    }

    /**
     * GET /api/study-groups/{id}/subjects/available
     * List subjects available to be assigned (not yet active in this rombel).
     */
    public function available(Request $request, string $studyGroupId): JsonResponse
    {
        $studyGroup = StudyGroup::findOrFail($studyGroupId);
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $assignedIds = StudyGroupSubject::where('study_group_id', $studyGroupId)
            ->where('is_active', true)
            ->pluck('subject_id')
            ->all();

        $subjects = Subject::where('school_id', $studyGroup->school_id)
            ->where('is_active', true)
            ->whereNotIn('id', $assignedIds)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'category', 'credit_hours']);

        return response()->json([
            'study_group_id' => $studyGroupId,
            'available' => $subjects,
        ]);
    }
}
