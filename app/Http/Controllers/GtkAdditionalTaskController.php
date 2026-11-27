<?php

namespace App\Http\Controllers;

use App\Models\AdditionalTaskSubject;
use App\Models\AdditionalTaskType;
use App\Models\GtkAdditionalTask;
use App\Models\InstitutionDecree;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use App\Services\GuruPendampingHours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GtkAdditionalTaskController extends Controller
{
    public function __construct(private readonly GuruPendampingHours $pendamping) {}

    public function index(Request $request, string $userId)
    {
        $query = GtkAdditionalTask::with(['user', 'decree']);

        $schoolId = $request->attributes->get('schoolContextId');
        $isGlobal = canPermission('gtk-additional-task-all-access');

        if ($request->filled('teacher_id')) {
            $query->where('user_id', $request->teacher_id);
        }

        if ($request->filled('decree_id')) {
            $query->where('decree_id', $request->decree_id);
        }

        $tasks = $query->orderBy('user_id')->orderBy('nama_tugas')->paginate(20)->withQueryString();

        $nonTeachingIds = usersHavingPermission('general_staff.ineligible');
        $teachers = User::query()
            ->when(! empty($nonTeachingIds), fn ($q) => $q->whereNotIn('users.id', $nonTeachingIds))
            ->when($schoolId, fn ($q) => $q->whereIn('users.id', \App\Services\SchoolGroupService::gtkUserIdsForSchool($schoolId)))
            ->orderBy('name')->get();

        $decrees = InstitutionDecree::where('decree_type', 'SK Pembagian Tugas')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('issued_date')->get();

        $canManage = $this->canManage();

        return view('gtk-additional-tasks.index', compact('tasks', 'teachers', 'decrees', 'userId', 'canManage'));
    }

    public function create(Request $request, string $userId)
    {
        abort_unless($this->canManage(), 403, 'Hanya Personalia, Administrator, atau Super Admin yang dapat menambah tugas tambahan.');

        return view('gtk-additional-tasks.create', $this->formData($request, $userId));
    }

    public function store(Request $request, string $userId)
    {
        abort_unless($this->canManage(), 403, 'Hanya Personalia, Administrator, atau Super Admin yang dapat menambah tugas tambahan.');

        $data = $this->validated($request);
        $pendamping = $this->pendampingSetup($request, $data);

        $task = DB::transaction(function () use ($data, $pendamping) {
            $task = GtkAdditionalTask::create($this->mergeHours($data, $pendamping));

            if ($pendamping) {
                $task->studyGroups()->sync($pendamping['group_ids']);
            }

            return $task;
        });

        return redirect()->route('user.gtk-additional-tasks.index', ['userId' => $userId])
            ->with('success', $this->successMessage('Tugas tambahan berhasil disimpan.', $pendamping, $task));
    }

    public function show(string $userId, string $id)
    {
        $task = GtkAdditionalTask::with(['user', 'decree', 'studyGroups', 'additionalTaskType.subject'])->findOrFail($id);

        $schoolId = request()->attributes->get('schoolContextId');
        $isGlobal = canPermission('gtk-additional-task-all-access');

        if (! $isGlobal && $task->decree && $schoolId && $task->decree->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        return view('gtk-additional-tasks.show', compact('task', 'userId'));
    }

    public function edit(Request $request, string $userId, string $id)
    {
        abort_unless($this->canManage(), 403, 'Hanya Personalia, Administrator, atau Super Admin yang dapat mengedit tugas tambahan.');

        $task = GtkAdditionalTask::with('studyGroups')->findOrFail($id);

        $schoolId = $request->attributes->get('schoolContextId');
        $isGlobal = canPermission('gtk-additional-task-all-access');

        if (! $isGlobal && $task->decree && $schoolId && $task->decree->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        return view('gtk-additional-tasks.edit', $this->formData($request, $userId, $task));
    }

    public function update(Request $request, string $userId, string $id)
    {
        abort_unless($this->canManage(), 403, 'Hanya Personalia, Administrator, atau Super Admin yang dapat mengedit tugas tambahan.');

        $task = GtkAdditionalTask::findOrFail($id);
        $data = $this->validated($request);
        $pendamping = $this->pendampingSetup($request, $data);

        DB::transaction(function () use ($task, $data, $pendamping) {
            $task->update($this->mergeHours($data, $pendamping));

            // Ganti mapel tugas berarti kelas lamanya tidak lagi relevan.
            if ($pendamping) {
                $task->studyGroups()->sync($pendamping['group_ids']);
            } else {
                $task->studyGroups()->detach();
            }
        });

        return redirect()->route('user.gtk-additional-tasks.index', ['userId' => $userId])
            ->with('success', $this->successMessage('Tugas tambahan berhasil diperbarui.', $pendamping, $task));
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        abort_unless($this->canManage(), 403, 'Hanya Personalia, Administrator, atau Super Admin yang dapat menghapus tugas tambahan.');

        $task = GtkAdditionalTask::findOrFail($id);
        $task->delete();

        return redirect()->route('user.gtk-additional-tasks.index', ['userId' => $userId])
            ->with('success', 'Tugas tambahan berhasil dihapus.');
    }

    /**
     * JP per kelas untuk mapel Guru Pendamping — dibaca dari Peta Kurikulum.
     *
     * Mapel bisa dikirim langsung dari form (sebelum disimpan ke master),
     * sehingga pratinjau JP sudah benar walau data belum tersimpan.
     */
    public function pendampingHours(Request $request)
    {
        abort_unless($this->canManage(), 403);

        $groupIds = array_values(array_filter((array) $request->input('group_ids', [])));
        $subjectIds = array_values(array_filter((array) $request->input('subject_ids', [])));
        $type = AdditionalTaskType::resolve($request->input('task_type_id'), $request->input('nama_tugas'));

        if ($subjectIds !== []) {
            $type = $this->typeWithSubjects($type, $subjectIds);
        }

        $rows = $this->pendamping->forGroups($type, $groupIds, $request->attributes->get('schoolContextId'));

        return response()->json([
            'subjects' => $this->pendamping->subjectsFor($type),
            'groups' => array_values($rows),
            'total' => array_sum(array_column($rows, 'total')),
        ]);
    }

    /**
     * Salinan tipe tugas dengan daftar mapel dari form (tidak disimpan).
     */
    private function typeWithSubjects(?AdditionalTaskType $type, array $subjectIds): AdditionalTaskType
    {
        $type ??= new AdditionalTaskType(['nama' => 'Guru Pendamping', 'kode' => 'guru-pendamping']);

        $subjectIds = array_values(array_unique(array_map('strval', $subjectIds)));

        // Bangun baris mapel di memori: mapel dari form belum tentu tersimpan
        // di master, jadi tidak boleh query tabel pivot.
        $rows = new \Illuminate\Database\Eloquent\Collection(collect($subjectIds)->values()->map(fn ($subjectId, $i) => new AdditionalTaskSubject([
            'additional_task_type_id' => $type->id,
            'subject_id' => $subjectId,
            'urutan' => $i,
        ]))->all());

        $type->setRelation('subjects', $rows);

        return $type;
    }

    /**
     * Data form: master nama tugas, mapel, dan kelas.
     */
    private function formData(Request $request, string $userId, ?GtkAdditionalTask $task = null): array
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $nonTeachingIds = usersHavingPermission('general_staff.ineligible');
        $teachers = User::query()
            ->when(! empty($nonTeachingIds), fn ($q) => $q->whereNotIn('users.id', $nonTeachingIds))
            ->when($schoolId, fn ($q) => $q->whereIn('users.id', \App\Services\SchoolGroupService::gtkUserIdsForSchool($schoolId)))
            ->orderBy('name')->get();

        $decrees = InstitutionDecree::where('decree_type', 'SK Pembagian Tugas')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('issued_date')->get();

        $taskTypes = AdditionalTaskType::query()
            ->active()
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode']);

        $defaultTasks = $taskTypes->pluck('nama')->unique()->values()->all();

        if (empty($defaultTasks)) {
            $defaultTasks = [
                'Kesiswawan',
                'Waka Kurikulum',
                'Waka Kesiswaan',
                'Waka Sarana & Prasarana',
                'Coordinator Bahasa Arab',
                'Guru Koordinator OSIPP',
                'Coordinator OSIS',
                'Coordinator Ekstrakurikuler',
                'Wali Kelas',
                'Guru BK',
                'Koordinator Laboratorium',
                'Koordinator Perpustakaan',
            ];
        }

        // Mapel aktif satuan pendidikan — sumber pilihan Guru Pendamping.
        // Tanpa konteks sekolah (super admin), kelompokkan per satuan
        // pendidikan agar mapel bernama sama tidak tampil berulang tanpa
        // keterangan.
        $subjects = Subject::query()
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('school:id,name')
            ->orderBy('name')
            ->get(['id', 'school_id', 'name', 'code'])
            ->groupBy(fn ($subject) => $schoolId ? 'Mapel aktif' : (string) ($subject->school?->name ?? 'Lainnya'));

        $studyGroups = StudyGroup::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $guruPendamping = AdditionalTaskType::guruPendamping();

        return compact(
            'teachers', 'decrees', 'defaultTasks', 'taskTypes', 'subjects',
            'studyGroups', 'guruPendamping', 'task', 'userId',
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'user_id' => 'required|exists:users,id',
            'decree_id' => 'nullable|exists:institution_decrees,id',
            'nama_tugas' => 'required|string|max:150',
            'hours_per_week' => 'nullable|integer|min:0|max:40',
            'nomor_sk' => 'nullable|string|max:100',
            'tmt' => 'nullable|date',
            'tst' => 'nullable|date|after_or_equal:tmt',
            'pendamping_subjects' => 'nullable|array',
            'pendamping_subjects.*' => 'string|exists:subjects,id',
            'pendamping_groups' => 'nullable|array',
            'pendamping_groups.*' => 'string|exists:study_groups,id',
        ]);
    }

    /**
     * Siapkan data Guru Pendamping: mapel ke master tipe, kelas ke baris tugas,
     * dan JP hasil hitungan Peta Kurikulum.
     *
     * @return array{type:AdditionalTaskType, group_ids:array<int,string>, total:int}|null
     */
    private function pendampingSetup(Request $request, array $data): ?array
    {
        $type = AdditionalTaskType::resolve(null, $data['nama_tugas']);

        if (! $type || ! $type->isGuruPendamping()) {
            return null;
        }

        $request->validate([
            'pendamping_subjects' => 'required|array|min:1',
            'pendamping_groups' => 'required|array|min:1',
        ], [
            'pendamping_subjects.required' => 'Pilih minimal satu mapel untuk Guru Pendamping.',
            'pendamping_groups.required' => 'Pilih minimal satu kelas untuk Guru Pendamping.',
        ]);

        $subjectIds = array_values(array_unique((array) $data['pendamping_subjects']));

        // Mapel disimpan di master tipe tugas, bukan per GTK: seluruh guru
        // pendamping memakai daftar mapel yang sama.
        DB::transaction(function () use ($type, $subjectIds) {
            $type->subjects()->delete();

            foreach ($subjectIds as $i => $subjectId) {
                $type->subjects()->create(['subject_id' => $subjectId, 'urutan' => $i]);
            }
        });

        $groupIds = array_values(array_unique((array) $data['pendamping_groups']));

        return [
            'type' => $type->fresh('subjects'),
            'group_ids' => $groupIds,
            'total' => $this->pendamping->totalHours(
                $type->fresh('subjects'),
                $groupIds,
                $request->attributes->get('schoolContextId'),
            ),
        ];
    }

    /**
     * Untuk Guru Pendamping, JP dihitung dari Peta Kurikulum dan mengalahkan
     * isian manual — supaya angka di tugas tambahan selalu sama dengan Peta.
     */
    private function mergeHours(array $data, ?array $pendamping): array
    {
        if ($pendamping) {
            $data['hours_per_week'] = $pendamping['total'];
        }

        return $data;
    }

    private function successMessage(string $base, ?array $pendamping, GtkAdditionalTask $task): string
    {
        if (! $pendamping) {
            return $base;
        }

        if ($pendamping['total'] <= 0) {
            return $base.' JP belum terhitung karena JP mapel di Peta Kurikulum masih 0. Atur dulu di Peta Kurikulum.';
        }

        return $base.' Total JP Guru Pendamping: '.$pendamping['total'].' JP.';
    }

    /**
     * Check if current user can manage additional tasks (Personalia, Administrator, Super Admin)
     */
    private function canManage(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ((method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())) {
            return true;
        }

        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->map(fn ($role) => mb_strtolower((string) $role))->all()
            : [];

        foreach ($roles as $role) {
            if (in_array($role, ['personalia', 'administrator', 'super admin', 'humas personalia'], true)
                || str_contains($role, 'personalia')) {
                return true;
            }
        }

        $jabatan = mb_strtolower(trim((string) ($user->employment?->jabatan ?? '')));

        return str_contains($jabatan, 'personalia')
            || (function_exists('canPermission') && canPermission('gtk-additional-task-all-access'));
    }
}
