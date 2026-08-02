<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;

class OtherTeacherTaskController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = OtherTeacherTask::with(['teacher', 'studyGroup', 'academicYear'])
            ->where('academic_year_id', function ($q) {
                $q->select('id')->from('academic_years')->where('is_active', true)->limit(1);
            });

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        $tasks = $query->orderBy('teacher_id')->paginate(20)->withQueryString();
        $schools = School::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $nonTeachingIds = usersHavingPermission('general_staff.ineligible');
        $teachers = User::query()
            ->when(! empty($nonTeachingIds), fn ($q) => $q->whereNotIn('users.id', $nonTeachingIds))
            ->orderBy('name')
            ->get();

        return view('other-teacher-tasks.index', compact(
            'tasks', 'schools', 'academicYears', 'teachers', 'userId'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $rules = [
            'teacher_id' => 'required|exists:users,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'task_name' => 'required|string|max:100',
            'task_code' => 'nullable|string|max:50',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'weekly_hours' => 'required|integer|min:0|max:40',
            'notes' => 'nullable|string|max:255',
        ];
        if (! $schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);
        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        OtherTeacherTask::create($data);

        return back()->with('success', 'Tugas lain berhasil ditambahkan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $task = OtherTeacherTask::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $task->school_id !== $schoolId) {
            abort(403);
        }

        $rules = [
            'task_name' => 'required|string|max:100',
            'task_code' => 'nullable|string|max:50',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'weekly_hours' => 'required|integer|min:0|max:40',
            'notes' => 'nullable|string|max:255',
        ];

        $data = $request->validate($rules);
        $task->update($data);

        return back()->with('success', 'Tugas lain berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $task = OtherTeacherTask::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $task->school_id !== $schoolId) {
            abort(403);
        }

        $task->delete();

        return back()->with('success', 'Tugas lain berhasil dihapus.');
    }
}
