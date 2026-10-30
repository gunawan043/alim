<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AdditionalTaskType;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;

class OtherTeacherTaskController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = OtherTeacherTask::with(['teacher', 'studyGroup', 'academicYear']);

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        } else {
            // Default: tahun ajaran yang sedang aktif
            $query->where('academic_year_id', function ($q) {
                $q->select('id')->from('academic_years')->where('is_active', true)->limit(1);
            });
        }

        if (in_array($request->get('status'), ['aktif', 'nonaktif'], true)) {
            $query->where('is_active', $request->get('status') === 'aktif');
        }

        // Statistik ringan dari query terfilter (tanpa order/paginate)
        $stats = [
            'total' => (clone $query)->count(),
            'jenis' => (clone $query)->distinct()->count('task_name'),
            'guru' => (clone $query)->distinct()->count('teacher_id'),
        ];
        $stats['aktif'] = (clone $query)->where('is_active', true)->count();
        $stats['nonaktif'] = $stats['total'] - $stats['aktif'];

        $tasks = (clone $query)->orderBy('teacher_id')->paginate(20)->withQueryString();

        // Privasi antar sekolah: selain Super Admin/Pimpinan, batasi ke satuan sendiri.
        $isGlobalView = $request->attributes->get('isGlobalView') === true;
        $scopeSchoolId = $isGlobalView ? null : $schoolId;

        $schools = $scopeSchoolId ? School::where('id', $scopeSchoolId)->get() : School::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $nonTeachingIds = usersHavingPermission('general_staff.ineligible');
        $teachers = User::query()
            ->when(! empty($nonTeachingIds), fn ($q) => $q->whereNotIn('users.id', $nonTeachingIds))
            ->when($scopeSchoolId, fn ($q) => $q->whereIn('users.id', \App\Services\SchoolGroupService::userIdsForSchool($scopeSchoolId)))
            ->orderBy('name')
            ->get();

        $activeAcademicYearId = $request->input('academic_year_id')
            ?: \App\Models\AcademicYear::where('is_active', true)->value('id');

        // Master nama tugas tambahan — hanya yang relevan untuk satuan pendidikan.
        $taskTypes = \App\Models\AdditionalTaskType::query()
            ->active()
            ->whereHas('jenisGtk', fn ($q) => $q->where('nama', 'like', '%Satuan Pendidikan%'))
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode']);

        // Rombel untuk tugas berbasis kelas (mis. Wali Kelas) — TA aktif, fallback semua.
        $studyGroups = \App\Models\StudyGroup::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->when($activeAcademicYearId, fn ($q) => $q->where('academic_year_id', $activeAcademicYearId))
            ->with('gradeLevel:id,name')
            ->orderedByGrade()
            ->get();

        if ($studyGroups->isEmpty()) {
            $studyGroups = \App\Models\StudyGroup::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('is_active', true)
                ->with('gradeLevel:id,name')
                ->orderBy('name')
                ->get();
        }

        return view('other-teacher-tasks.index', compact(
            'tasks', 'schools', 'academicYears', 'teachers', 'userId', 'stats', 'activeAcademicYearId', 'taskTypes', 'studyGroups'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $rules = [
            'teacher_id' => 'required|exists:users,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'additional_task_type_id' => 'nullable|exists:additional_task_types,id',
            'task_name' => 'nullable|string|max:100',
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

        // Nama & kode tugas WAJIB berasal dari master additional_task_types.
        $type = AdditionalTaskType::resolve(
            $data['additional_task_type_id'] ?? null,
            $data['task_name'] ?? null,
            $data['task_code'] ?? null
        );

        if (! $type) {
            return back()->withInput()->with('error', 'Jenis tugas tambahan tidak dikenali. Pilih dari daftar tugas yang tersedia.');
        }

        $data['additional_task_type_id'] = $type->id;
        $data['task_name'] = $type->nama;
        $data['task_code'] = $type->kode;

        // Tugas berbasis kelas (mis. Wali Kelas) wajib memilih rombel; rombel harus milik sekolah.
        if ($type->kode === 'wali-kelas' && empty($data['study_group_id'])) {
            return back()->withInput()->with('error', 'Untuk tugas "Wali Kelas", pilih rombel/kelas terlebih dahulu.');
        }

        if (! empty($data['study_group_id'])) {
            $owner = \App\Models\StudyGroup::whereKey($data['study_group_id'])->value('school_id');
            if ($owner && ! empty($data['school_id']) && (string) $owner !== (string) $data['school_id']) {
                abort(403, 'Rombel harus berada di satuan pendidikan Anda.');
            }
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
            'additional_task_type_id' => 'nullable|exists:additional_task_types,id',
            'task_name' => 'nullable|string|max:100',
            'task_code' => 'nullable|string|max:50',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'weekly_hours' => 'required|integer|min:0|max:40',
            'notes' => 'nullable|string|max:255',
        ];

        $data = $request->validate($rules);

        $type = AdditionalTaskType::resolve(
            $data['additional_task_type_id'] ?? null,
            $data['task_name'] ?? null,
            $data['task_code'] ?? null
        );

        if (! $type) {
            return back()->withInput()->with('error', 'Jenis tugas tambahan tidak dikenali. Pilih dari daftar tugas yang tersedia.');
        }

        $data['additional_task_type_id'] = $type->id;
        $data['task_name'] = $type->nama;
        $data['task_code'] = $type->kode;

        // Tugas berbasis kelas (mis. Wali Kelas) wajib memilih rombel.
        if ($type->kode === 'wali-kelas' && empty($data['study_group_id'])) {
            return back()->withInput()->with('error', 'Untuk tugas "Wali Kelas", pilih rombel/kelas terlebih dahulu.');
        }

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
