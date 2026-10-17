<?php

namespace App\Http\Controllers\SatuanKerja;

use App\Models\AdditionalTaskType;
use App\Models\GtkAdditionalTask;
use App\Models\GtkWorkUnit;
use App\Models\InstitutionDecree;
use App\Models\OtherTeacherTask;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaskController
{
    public function additionalTasks(Request $request, string $userId, string $workUnitId)
    {
        $user = auth()->user();
        $workUnit = WorkUnit::where('id', $workUnitId)
            ->where('is_active', true)
            ->firstOrFail();

        $userWorkUnits = GtkWorkUnit::where('user_id', $user->id)
            ->whereHas('workUnit', fn($q) => $q->where('id', $workUnitId))
            ->exists();

        abort_unless($userWorkUnits, 403, 'Anda tidak memiliki akses ke satuan kerja ini.');

        $query = GtkAdditionalTask::with(['user', 'decree', 'workUnit'])
            ->where('work_unit_id', $workUnitId);

        if ($request->filled('teacher_id')) {
            $query->where('user_id', $request->teacher_id);
        }

        if ($request->filled('decree_id')) {
            $query->where('decree_id', $request->decree_id);
        }

        $tasks = $query->orderBy('user_id')->orderBy('nama_tugas')->paginate(20)->withQueryString();

        $teachers = User::whereHas('gtkWorkUnits', fn($q) => $q->where('work_unit_id', $workUnitId))
            ->orderBy('name')
            ->get();

        $decrees = InstitutionDecree::where('decree_type', 'SK Pembagian Tugas')
            ->whereHas('workUnit', fn($q) => $q->where('id', $workUnitId))
            ->orderByDesc('issued_date')
            ->get();

        return view('satuan-kerja.tasks.additional', compact('tasks', 'teachers', 'decrees', 'workUnit', 'userId'));
    }

    public function storeAdditionalTask(Request $request, string $userId, string $workUnitId)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'decree_id' => 'nullable|exists:institution_decrees,id',
            'nama_tugas' => 'required|string|max:150',
            'hours_per_week' => 'nullable|integer|min:0|max:40',
            'nomor_sk' => 'nullable|string|max:100',
            'tmt' => 'nullable|date',
            'tst' => 'nullable|date|after_or_equal:tmt',
        ]);

        GtkAdditionalTask::create(array_merge($data, ['work_unit_id' => $workUnitId]));

        return redirect()->route('user.satuan-kerja.additional-tasks', ['workUnitId' => $workUnitId, 'userId' => $userId])
            ->with('success', 'Tugas tambahan berhasil disimpan.');
    }

    public function updateAdditionalTask(Request $request, string $userId, string $workUnitId, string $id)
    {
        $task = GtkAdditionalTask::where('id', $id)
            ->where('work_unit_id', $workUnitId)
            ->firstOrFail();

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'decree_id' => 'nullable|exists:institution_decrees,id',
            'nama_tugas' => 'required|string|max:150',
            'hours_per_week' => 'nullable|integer|min:0|max:40',
            'nomor_sk' => 'nullable|string|max:100',
            'tmt' => 'nullable|date',
            'tst' => 'nullable|date|after_or_equal:tmt',
        ]);

        $task->update($data);

        return redirect()->route('user.satuan-kerja.additional-tasks', ['workUnitId' => $workUnitId, 'userId' => $userId])
            ->with('success', 'Tugas tambahan berhasil diperbarui.');
    }

    public function destroyAdditionalTask(string $userId, string $workUnitId, string $id)
    {
        $task = GtkAdditionalTask::where('id', $id)
            ->where('work_unit_id', $workUnitId)
            ->firstOrFail();

        $task->delete();

        return redirect()->route('user.satuan-kerja.additional-tasks', ['workUnitId' => $workUnitId, 'userId' => $userId])
            ->with('success', 'Tugas tambahan berhasil dihapus.');
    }

    // ─── Other Teacher Tasks (Tugas Tambahan Guru) ───────────────────────

    public function otherTasks(Request $request, string $userId, string $workUnitId)
    {
        $user = auth()->user();
        $workUnit = WorkUnit::where('id', $workUnitId)
            ->where('is_active', true)
            ->firstOrFail();

        $userWorkUnits = GtkWorkUnit::where('user_id', $user->id)
            ->whereHas('workUnit', fn($q) => $q->where('id', $workUnitId))
            ->exists();

        abort_unless($userWorkUnits, 403, 'Anda tidak memiliki akses ke satuan kerja ini.');

        $query = OtherTeacherTask::with(['teacher', 'studyGroup', 'academicYear', 'workUnit'])
            ->where('work_unit_id', $workUnitId)
            ->where('academic_year_id', function ($q) {
                $q->select('id')->from('academic_years')->where('is_active', true)->limit(1);
            });

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        $tasks = $query->orderBy('teacher_id')->paginate(20)->withQueryString();

        $teachers = User::whereHas('gtkWorkUnits', fn($q) => $q->where('work_unit_id', $workUnitId))
            ->orderBy('name')
            ->get();

        $taskTypes = AdditionalTaskType::active()
            ->whereHas('jenisGtk', fn($q) => $q->whereIn('nama', ['Guru', 'Tenaga Pendidik Pondok']))
            ->get();

        return view('satuan-kerja.tasks.other', compact('tasks', 'teachers', 'taskTypes', 'workUnit', 'userId'));
    }

    public function storeOtherTask(Request $request, string $userId, string $workUnitId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $data = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'task_name' => 'required|string|max:100',
            'task_code' => 'nullable|string|max:50',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'weekly_hours' => 'required|integer|min:0|max:40',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $data['work_unit_id'] = $workUnitId;
        $data['is_active'] = true;

        OtherTeacherTask::create($data);

        return back()->with('success', 'Tugas tambahan guru berhasil ditambahkan.');
    }

    public function updateOtherTask(Request $request, string $userId, string $workUnitId, string $id)
    {
        $task = OtherTeacherTask::where('id', $id)
            ->where('work_unit_id', $workUnitId)
            ->firstOrFail();

        $data = $request->validate([
            'task_name' => 'required|string|max:100',
            'task_code' => 'nullable|string|max:50',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'weekly_hours' => 'required|integer|min:0|max:40',
            'notes' => 'nullable|string|max:255',
        ]);

        $task->update($data);

        return back()->with('success', 'Tugas tambahan guru berhasil diperbarui.');
    }

    public function destroyOtherTask(string $userId, string $workUnitId, string $id)
    {
        $task = OtherTeacherTask::where('id', $id)
            ->where('work_unit_id', $workUnitId)
            ->firstOrFail();

        $task->delete();

        return back()->with('success', 'Tugas tambahan guru berhasil dihapus.');
    }
}
