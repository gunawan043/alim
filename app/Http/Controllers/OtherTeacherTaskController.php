<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AdditionalTaskType;
use App\Models\OtherTeacherTask;
use App\Models\OtherTeacherTaskSubject;
use App\Models\School;
use App\Models\User;
use App\Services\GuruPendampingHours;
use Illuminate\Http\Request;

class OtherTeacherTaskController extends Controller
{
    public function __construct(private GuruPendampingHours $pendamping)
    {
    }

    /**
     * Apakah tipe tugas yang dipilih di form adalah "Guru Pendamping".
     */
    private function selectedTypeIsPendamping(Request $request): bool
    {
        return AdditionalTaskType::resolve(
            $request->input('additional_task_type_id'),
            $request->input('task_name'),
            $request->input('task_code')
        )?->isGuruPendamping() ?? false;
    }

    /**
     * Rapikan input pasangan mapel–kelas dan ambil hanya pasangan yang
     * valid untuk tipe tugas ini (mapel harus terdaftar di master tipe).
     *
     * @param  mixed  $payload  subject_id => [study_group_id, ...]
     * @return array<string, array<int, string>>
     */
    private function fillPendampingPayload(AdditionalTaskType $type, mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        return $this->pendamping->pairing($type, $payload);
    }

    /**
     * Simpan ulang pasangan mapel–kelas milik satu tugas.
     *
     * @param  array<string, array<int, string>>|null  $payload
     */
    private function syncPendampingPairs(OtherTeacherTask $task, ?array $payload): void
    {
        $task->pendampingSubjects()->delete();

        if (! $payload) {
            return;
        }

        foreach ($payload as $subjectId => $groupIds) {
            foreach ($groupIds as $groupId) {
                OtherTeacherTaskSubject::create([
                    'other_teacher_task_id' => $task->id,
                    'subject_id' => $subjectId,
                    'study_group_id' => $groupId,
                ]);
            }
        }
    }

    public function index(Request $request, string $userId)
    {
        $query = OtherTeacherTask::with([
            'teacher',
            'studyGroup',
            'academicYear',
            'pendampingSubjects.subject:id,name',
            'pendampingSubjects.studyGroup:id,name',
        ]);

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
            ->when($scopeSchoolId, fn ($q) => $q->whereIn('users.id', \App\Services\SchoolGroupService::gtkUserIdsForSchool($scopeSchoolId)))
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

        // Mapel aktif — sumber pilihan Guru Pendamping. Tanpa konteks sekolah
        // (Super Admin) mapel dikelompokkan per satuan pendidikan.
        $subjects = \App\Models\Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->with('school:id,name')
            ->orderBy('name')
            ->get(['id', 'school_id', 'name', 'code'])
            ->groupBy(fn ($s) => $s->school?->name ?? 'Mapel aktif');

        return view('other-teacher-tasks.index', compact(
            'tasks', 'schools', 'academicYears', 'teachers', 'userId', 'stats', 'activeAcademicYearId', 'taskTypes', 'studyGroups', 'subjects'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $isPendamping = $this->selectedTypeIsPendamping($request);

        $rules = [
            'teacher_id' => 'required|exists:users,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'additional_task_type_id' => 'nullable|exists:additional_task_types,id',
            'task_name' => 'nullable|string|max:100',
            'task_code' => 'nullable|string|max:50',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'weekly_hours' => $isPendamping ? 'nullable|integer|min:0|max:200' : 'required|integer|min:0|max:40',
            'notes' => 'nullable|string|max:255',
            'pendamping' => 'nullable|array',
            'pendamping.*' => 'array',
            'pendamping.*.*' => 'exists:study_groups,id',
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

        $payload = $request->input('pendamping');

        if ($type->isGuruPendamping()) {
            // Guru Pendamping: mapel wajib, JP dihitung dari Peta Kurikulum.
            $payload = $this->fillPendampingPayload($type, $payload);

            if ($payload === []) {
                return back()->withInput()->with('error', 'Pilih minimal satu mapel dan satu kelas untuk Guru Pendamping.');
            }

            $data['study_group_id'] = null;
            $data['weekly_hours'] = $this->pendamping->totalHours($type, $payload, $data['school_id'] ?? $schoolId);
        } else {
            $payload = null;
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

        $task = OtherTeacherTask::create($data);
        $this->syncPendampingPairs($task, $payload);

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
            'weekly_hours' => 'nullable|integer|min:0|max:200',
            'notes' => 'nullable|string|max:255',
            'pendamping' => 'nullable|array',
            'pendamping.*' => 'array',
            'pendamping.*.*' => 'exists:study_groups,id',
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

        $payload = $request->input('pendamping');

        if ($type->isGuruPendamping()) {
            $payload = $this->fillPendampingPayload($type, $payload);

            if ($payload === []) {
                return back()->withInput()->with('error', 'Pilih minimal satu mapel dan satu kelas untuk Guru Pendamping.');
            }

            $data['study_group_id'] = null;
            $data['weekly_hours'] = $this->pendamping->totalHours($type, $payload, $task->school_id);
        } else {
            if (! isset($data['weekly_hours']) || $data['weekly_hours'] === null) {
                return back()->withInput()->with('error', 'Jam per minggu wajib diisi.');
            }
            $payload = null;
        }

        $data['additional_task_type_id'] = $type->id;
        $data['task_name'] = $type->nama;
        $data['task_code'] = $type->kode;

        // Tugas berbasis kelas (mis. Wali Kelas) wajib memilih rombel.
        if ($type->kode === 'wali-kelas' && empty($data['study_group_id'])) {
            return back()->withInput()->with('error', 'Untuk tugas "Wali Kelas", pilih rombel/kelas terlebih dahulu.');
        }

        $task->update($data);
        $this->syncPendampingPairs($task, $payload);

        return back()->with('success', 'Tugas lain berhasil diperbarui.');
    }

    /**
     * JP per kelas untuk mapel Guru Pendamping — dibaca dari Peta Kurikulum.
     */
    public function pendampingHours(Request $request)
    {
        $type = AdditionalTaskType::resolve($request->input('task_type_id'), $request->input('task_name'));

        if (! $type || ! $type->isGuruPendamping()) {
            return response()->json(['groups' => [], 'total' => 0]);
        }

        $groupIds = array_values(array_filter((array) $request->input('group_ids', [])));
        $rows = $this->pendamping->forGroups($type, $groupIds, $request->attributes->get('schoolContextId'));

        return response()->json([
            'groups' => array_values($rows),
            'total' => array_sum(array_column($rows, 'total')),
        ]);
    }

    public function destroy(Request $request, string $userId, string $id)    {
        $task = OtherTeacherTask::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $task->school_id !== $schoolId) {
            abort(403);
        }

        $task->pendampingSubjects()->delete();
        $task->delete();

        return back()->with('success', 'Tugas lain berhasil dihapus.');
    }
}
