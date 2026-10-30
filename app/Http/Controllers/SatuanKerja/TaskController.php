<?php

namespace App\Http\Controllers\SatuanKerja;

use App\Models\AdditionalTaskType;
use App\Models\AdditionalTaskTypeWorkUnitHour;
use App\Models\GtkEmployment;
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

        $query = GtkAdditionalTask::with(['user', 'decree', 'workUnit', 'additionalTaskType:id,nama,kode'])
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
            ->whereHas('school', fn ($q) => $q->where('work_unit_id', $workUnitId))
            ->orderByDesc('issued_date')
            ->get();

        $taskTypes = $this->taskTypesForWorkUnit($workUnitId);

        return view('satuan-kerja.tasks.additional', compact('tasks', 'teachers', 'decrees', 'workUnit', 'workUnitId', 'userId', 'taskTypes'));
    }

    public function taskHours(string $userId, string $workUnitId)
    {
        $workUnit = WorkUnit::where('id', $workUnitId)->where('is_active', true)->firstOrFail();
        $this->authorizeWorkUnit($workUnitId);

        $userIds = GtkWorkUnit::where('work_unit_id', $workUnitId)->pluck('user_id');
        $jenisIds = GtkEmployment::whereIn('user_id', $userIds)
            ->pluck('jenis_gtk_id')
            ->filter()
            ->unique();

        $taskTypes = AdditionalTaskType::active()
            ->when($jenisIds->isNotEmpty(), fn ($q) => $q->whereIn('jenis_gtk_id', $jenisIds))
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        if ($taskTypes->isEmpty()) {
            $taskTypes = AdditionalTaskType::active()->orderBy('urutan')->orderBy('nama')->get();
        }

        $hours = AdditionalTaskTypeWorkUnitHour::where('work_unit_id', $workUnitId)
            ->pluck('weekly_hours', 'additional_task_type_id');

        return view('satuan-kerja.tasks.hours', compact('workUnit', 'taskTypes', 'hours', 'workUnitId', 'userId'));
    }

    public function updateTaskHours(Request $request, string $userId, string $workUnitId)
    {
        $workUnit = WorkUnit::where('id', $workUnitId)->where('is_active', true)->firstOrFail();
        $this->authorizeWorkUnit($workUnitId);

        $data = $request->validate([
            'hours' => 'required|array',
            'hours.*' => 'nullable|numeric|min:0|max:40',
        ]);

        DB::transaction(function () use ($data, $workUnitId) {
            foreach ($data['hours'] as $taskTypeId => $value) {
                AdditionalTaskTypeWorkUnitHour::updateOrCreate(
                    [
                        'work_unit_id' => $workUnitId,
                        'additional_task_type_id' => $taskTypeId,
                    ],
                    ['weekly_hours' => $value ?? 0]
                );
            }
        });

        return back()->with('success', 'Pengaturan JP tugas tambahan berhasil disimpan.');
    }

    /**
     * Master jenis tugas yang relevan untuk sebuah satuan kerja, berdasarkan
     * jenis GTK para anggotanya. Fallback ke seluruh master aktif.
     */
    private function taskTypesForWorkUnit(string $workUnitId)
    {
        $userIds = GtkWorkUnit::where('work_unit_id', $workUnitId)->pluck('user_id');

        $jenisIds = GtkEmployment::whereIn('user_id', $userIds)
            ->pluck('jenis_gtk_id')
            ->filter()
            ->unique();

        $taskTypes = AdditionalTaskType::active()
            ->when($jenisIds->isNotEmpty(), fn ($q) => $q->whereIn('jenis_gtk_id', $jenisIds))
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        if ($taskTypes->isEmpty()) {
            $taskTypes = AdditionalTaskType::active()->orderBy('urutan')->orderBy('nama')->get();
        }

        return $taskTypes;
    }

    private function authorizeWorkUnit(string $workUnitId): void
    {
        $user = auth()->user();
        $allowed = GtkWorkUnit::where('user_id', $user->id)
            ->where('work_unit_id', $workUnitId)
            ->exists();

        abort_unless($allowed || canPermission('institution-decree-all-access'), 403, 'Anda tidak memiliki akses ke satuan kerja ini.');
    }

    public function storeAdditionalTask(Request $request, string $userId, string $workUnitId)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'decree_id' => 'nullable|exists:institution_decrees,id',
            'additional_task_type_id' => 'nullable|exists:additional_task_types,id',
            'nama_tugas' => 'nullable|string|max:150',
            'hours_per_week' => 'nullable|integer|min:0|max:40',
            'nomor_sk' => 'nullable|string|max:100',
            'tmt' => 'nullable|date',
            'tst' => 'nullable|date|after_or_equal:tmt',
        ]);

        $type = AdditionalTaskType::resolve(
            $data['additional_task_type_id'] ?? null,
            $data['nama_tugas'] ?? null
        );

        if (! $type) {
            return back()->withInput()->with('error', 'Jenis tugas tambahan tidak dikenali. Pilih dari daftar tugas yang tersedia.');
        }

        $data['additional_task_type_id'] = $type->id;
        $data['nama_tugas'] = $type->nama;

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
            'additional_task_type_id' => 'nullable|exists:additional_task_types,id',
            'nama_tugas' => 'nullable|string|max:150',
            'hours_per_week' => 'nullable|integer|min:0|max:40',
            'nomor_sk' => 'nullable|string|max:100',
            'tmt' => 'nullable|date',
            'tst' => 'nullable|date|after_or_equal:tmt',
        ]);

        $type = AdditionalTaskType::resolve(
            $data['additional_task_type_id'] ?? null,
            $data['nama_tugas'] ?? null
        );

        if (! $type) {
            return back()->withInput()->with('error', 'Jenis tugas tambahan tidak dikenali. Pilih dari daftar tugas yang tersedia.');
        }

        $data['additional_task_type_id'] = $type->id;
        $data['nama_tugas'] = $type->nama;

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

        $query = OtherTeacherTask::with(['teacher', 'studyGroup', 'academicYear', 'workUnit', 'additionalTaskType:id,nama,kode'])
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

        $taskTypes = $this->taskTypesForWorkUnit($workUnitId);

        $studyGroups = \App\Models\StudyGroup::query()
            ->where('school_id', $workUnit->school?->id ?? $request->attributes->get('schoolContextId'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('satuan-kerja.tasks.other', compact('tasks', 'teachers', 'taskTypes', 'workUnit', 'workUnitId', 'userId', 'studyGroups'));
    }

    public function storeOtherTask(Request $request, string $userId, string $workUnitId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $data = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'additional_task_type_id' => 'nullable|exists:additional_task_types,id',
            'task_name' => 'nullable|string|max:100',
            'task_code' => 'nullable|string|max:50',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'weekly_hours' => 'required|integer|min:0|max:40',
            'notes' => 'nullable|string|max:255',
        ]);

        $type = AdditionalTaskType::resolve(
            $data['additional_task_type_id'] ?? null,
            $data['task_name'] ?? null,
            $data['task_code'] ?? null
        );

        if (! $type) {
            return back()->withInput()->with('error', 'Jenis tugas tambahan tidak dikenali. Pilih dari daftar tugas yang tersedia.');
        }

        if ($type->kode === 'wali-kelas' && empty($data['study_group_id'])) {
            return back()->withInput()->with('error', 'Untuk tugas "Wali Kelas", pilih rombel/kelas terlebih dahulu.');
        }

        $data['additional_task_type_id'] = $type->id;
        $data['task_name'] = $type->nama;
        $data['task_code'] = $type->kode;

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
            'additional_task_type_id' => 'nullable|exists:additional_task_types,id',
            'task_name' => 'nullable|string|max:100',
            'task_code' => 'nullable|string|max:50',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'weekly_hours' => 'required|integer|min:0|max:40',
            'notes' => 'nullable|string|max:255',
        ]);

        $type = AdditionalTaskType::resolve(
            $data['additional_task_type_id'] ?? null,
            $data['task_name'] ?? null,
            $data['task_code'] ?? null
        );

        if (! $type) {
            return back()->withInput()->with('error', 'Jenis tugas tambahan tidak dikenali. Pilih dari daftar tugas yang tersedia.');
        }

        $studyGroupId = $data['study_group_id'] ?? $task->study_group_id;
        if ($type->kode === 'wali-kelas' && empty($studyGroupId)) {
            return back()->withInput()->with('error', 'Untuk tugas "Wali Kelas", pilih rombel/kelas terlebih dahulu.');
        }

        $data['additional_task_type_id'] = $type->id;
        $data['task_name'] = $type->nama;
        $data['task_code'] = $type->kode;

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
