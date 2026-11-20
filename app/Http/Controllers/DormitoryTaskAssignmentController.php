<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AdditionalTaskType;
use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\GtkWorkUnit;
use App\Models\Role;
use App\Models\StructuralPosition;
use App\Models\User;
use App\Services\Boarding\DormitoryPolicyAccess;
use App\Services\Dormitory\DormitoryAudit;
use App\Support\DormitoryPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Pembagian tugas staf asrama.
 *
 * Jabatan dan tugas tambahan dibaca dari master (`structural_positions` dan
 * `additional_task_types`) agar sejalan dengan SK Pembagian Tugas satuan
 * kerja. Yang khas asrama — shift, masa tugas, dan rincian bidang tugas —
 * tetap disimpan pada penugasan asrama.
 */
class DormitoryTaskAssignmentController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $this->authorizeManager($request->user(), $dormitory);

        $staffUsers = $this->staffUsers($dormitory);
        $assignments = $this->assignments($dormitory);

        $assignedUserIds = $assignments->keys()->push((string) $dormitory->head_id)->filter()->unique()->all();
        $availableUsers = $staffUsers
            ->reject(fn (User $user) => in_array((string) $user->id, $assignedUserIds, true))
            ->values();

        $rows = $this->assignmentRows($dormitory, $staffUsers, $assignments);

        return view('dormitory.task-assignments.index', [
            'dormitory' => $dormitory,
            'userId' => $userId,
            'rows' => $rows,
            'availableUsers' => $availableUsers,
            'positions' => $this->asramaPositions(),
            'taskTypes' => $this->taskTypesFor(),
            'duties' => DormitoryPosition::duties(),
            'shifts' => DormitoryPosition::shiftOptions(),
            'stats' => [
                'total' => $rows->count(),
                'leaders' => $rows->whereIn('position_name', [
                    'Kepala Asrama',
                    'Wakil Kepala Asrama',
                ])->count(),
                'duties' => $rows->sum(fn (array $row) => count($row['duties'] ?? [])),
                'tasks' => $rows->sum(fn (array $row) => count($row['task_types'] ?? [])),
            ],
        ]);
    }

    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $this->authorizeManager($request->user(), $dormitory);

        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')],
            'position_id' => $this->positionRules(),
            'shift' => 'nullable|in:'.implode(',', array_keys(DormitoryPosition::shiftOptions())),
            'duties' => 'nullable|array',
            'duties.*' => 'string|in:'.implode(',', DormitoryPosition::dutyKeys()),
            'task_type_ids' => 'nullable|array',
            'task_type_ids.*' => ['string', Rule::exists('additional_task_types', 'id')],
            'jp' => 'nullable|array',
            'jp.*' => 'nullable|integer|min:0|max:80',
            'nomor_sk' => 'nullable|string|max:100',
            'tmt' => 'nullable|date',
            'tst' => 'nullable|date|after_or_equal:tmt',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $this->assertEligibleUser($dormitory, $data['user_id']);

        $assignment = DormitoryStaffAssignment::withTrashed()
            ->where('user_id', $data['user_id'])
            ->where('dormitory_id', $asramaUuid)
            ->first();

        DB::transaction(function () use ($data, &$assignment, $dormitory, $request) {
            $payload = $this->payload($data, $request->user()->id);

            if ($assignment) {
                $assignment->restore();
                $assignment->update($payload);
            } else {
                $assignment = DormitoryStaffAssignment::create($payload + [
                    'user_id' => $data['user_id'],
                    'dormitory_id' => $dormitory->id,
                    'assigned_by_id' => $request->user()->id,
                    'status' => 'active',
                ]);
            }

            $assignment->taskTypes()->sync($this->taskTypeSyncPayload($data));
            $this->syncWorkUnitPosition($dormitory, $data['user_id'], $data['position_id'] ?? null);
        });

        DormitoryAudit::log('DORMITORY_TASK_ASSIGNMENT_SAVED', 'dormitory_staff_assignments', $assignment->id);

        return back()->with('success', 'Pembagian tugas staf asrama berhasil disimpan.');
    }

    public function update(Request $request, string $userId, string $asramaUuid, string $assignmentUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $this->authorizeManager($request->user(), $dormitory);

        $assignment = DormitoryStaffAssignment::where('dormitory_id', $asramaUuid)->findOrFail($assignmentUuid);

        $data = $request->validate([
            'position_id' => $this->positionRules(),
            'shift' => 'nullable|in:'.implode(',', array_keys(DormitoryPosition::shiftOptions())),
            'duties' => 'nullable|array',
            'duties.*' => 'string|in:'.implode(',', DormitoryPosition::dutyKeys()),
            'task_type_ids' => 'nullable|array',
            'task_type_ids.*' => ['string', Rule::exists('additional_task_types', 'id')],
            'jp' => 'nullable|array',
            'jp.*' => 'nullable|integer|min:0|max:80',
            'nomor_sk' => 'nullable|string|max:100',
            'tmt' => 'nullable|date',
            'tst' => 'nullable|date|after_or_equal:tmt',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
        ], $this->validationMessages());

        DB::transaction(function () use ($assignment, $data, $dormitory, $request) {
            $assignment->update($this->payload($data, $request->user()->id));
            $assignment->taskTypes()->sync($this->taskTypeSyncPayload($data));
            $this->syncWorkUnitPosition($dormitory, $assignment->user_id, $data['position_id'] ?? null);
        });

        DormitoryAudit::log('DORMITORY_TASK_ASSIGNMENT_UPDATED', 'dormitory_staff_assignments', $assignment->id);

        return back()->with('success', 'Pembagian tugas berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $asramaUuid, string $assignmentUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $this->authorizeManager($request->user(), $dormitory);

        $assignment = DormitoryStaffAssignment::where('dormitory_id', $asramaUuid)->findOrFail($assignmentUuid);
        $assignment->update([
            'status' => 'ended',
            'end_date' => now()->toDateString(),
            'updated_by_id' => $request->user()->id,
        ]);

        DormitoryAudit::log('DORMITORY_TASK_ASSIGNMENT_ENDED', 'dormitory_staff_assignments', $assignment->id);

        return back()->with('success', 'Penugasan staf di asrama ini diakhiri.');
    }

    public function print(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $this->authorizeManager($request->user(), $dormitory);

        $staffUsers = $this->staffUsers($dormitory);
        $assignments = $this->assignments($dormitory);

        return view('dormitory.task-assignments.print', [
            'dormitory' => $dormitory,
            'userId' => $userId,
            'rows' => $this->assignmentRows($dormitory, $staffUsers, $assignments),
            'academicYear' => AcademicYear::where('is_active', true)->first(),
            'printedAt' => now(),
        ]);
    }

    private function authorizeManager($user, Dormitory $dormitory): void
    {
        abort_unless(
            $user && app(DormitoryPolicyAccess::class)->canManageQuota($user, $dormitory->id),
            403,
            'Hanya Kepala/Wakil Asrama yang dapat mengatur pembagian tugas.'
        );
    }

    /**
     * Jabatan yang boleh dipilih: master jabatan untuk role Asrama.
     * Kepala Asrama dikecualikan karena ditetapkan dari Profil Asrama.
     *
     * @return Collection<int, StructuralPosition>
     */
    private function asramaPositions(): Collection
    {
        $roleId = Role::where('name', 'Asrama')->where('guard_name', 'web')->value('id');

        // Tanpa role Asrama jangan tampilkan seluruh master jabatan sekolah.
        if (! $roleId) {
            return collect();
        }

        return StructuralPosition::query()
            ->where('role_id', $roleId)
            ->where('is_active', true)
            ->whereRaw("LOWER(name) NOT LIKE 'kepala asrama%'")
            ->orderBy('urutan')
            ->orderBy('name')
            ->get();
    }

    /**
     * Master jenis tugas tambahan yang relevan, ditentukan dari jenis GTK
     * jabatan-jabatan asrama (Tenaga Pengasuhan & Keasramaan), bukan dari
     * seluruh staf satuan pendidikan.
     *
     * @return Collection<int, AdditionalTaskType>
     */
    private function taskTypesFor(): Collection
    {
        $jenisIds = $this->asramaPositions()
            ->pluck('jenis_gtk_id')
            ->filter()
            ->unique();

        return AdditionalTaskType::active()
            ->when($jenisIds->isNotEmpty(), fn ($query) => $query->whereIn('jenis_gtk_id', $jenisIds))
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();
    }

    /**
     * @return array<int, mixed>
     */
    private function positionRules(): array
    {
        return [
            'required',
            'string',
            Rule::in($this->asramaPositions()->pluck('id')->all()),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'position_id.required' => 'Pilih jabatan dari master jabatan asrama.',
            'position_id.in' => 'Jabatan tidak tersedia untuk asrama. Pilih dari daftar master jabatan.',
            'task_type_ids.*.exists' => 'Jenis tugas tambahan tidak dikenali.',
            'jp.*.integer' => 'JP per minggu harus berupa angka.',
            'jp.*.max' => 'JP per minggu maksimal 80.',
        ];
    }

    /**
     * Bentuk payload sync(): id jenis tugas => kolom pivot.
     *
     * @return array<string, array{hours_per_week: int|null}>
     */
    private function taskTypeSyncPayload(array $data): array
    {
        $hours = $data['jp'] ?? [];

        return collect($data['task_type_ids'] ?? [])
            ->unique()
            ->mapWithKeys(fn ($id) => [$id => [
                'hours_per_week' => isset($hours[$id]) && $hours[$id] !== ''
                    ? (int) $hours[$id]
                    : null,
            ]])
            ->all();
    }

    private function assertEligibleUser(Dormitory $dormitory, string $targetUserId): void
    {
        $target = User::with(['employment', 'gtkWorkUnits'])->findOrFail($targetUserId);

        $sameSchool = $dormitory->school_id
            && (string) $target->employment?->school_id === (string) $dormitory->school_id;

        $sameWorkUnit = $dormitory->work_unit_id
            && $target->gtkWorkUnits->contains(fn ($row) => (string) $row->work_unit_id === (string) $dormitory->work_unit_id);

        $alreadyAssigned = DormitoryStaffAssignment::where('user_id', $target->id)
            ->where('dormitory_id', $dormitory->id)
            ->where('status', 'active')
            ->exists();

        if (! $target->employment || (! $sameSchool && ! $sameWorkUnit && ! $alreadyAssigned)) {
            abort(422, 'Staf harus berasal dari satuan pendidikan/satuan kerja asrama ini.');
        }
    }

    private function payload(array $data, string $actorId): array
    {
        $position = isset($data['position_id'])
            ? StructuralPosition::find($data['position_id'])
            : null;

        return [
            'position_id' => $position?->id,
            // Kolom legacy `position` menyimpan slug karena masih dibaca
            // DormitoryPolicyAccess & notifier kepala asrama. Nama yang
            // ditampilkan selalu dibaca dari relasi structuralPosition.
            'position' => $position ? DormitoryPosition::fromEmployment($position->name) : null,
            'shift' => $data['shift'] ?? null,
            'duties' => array_values(array_unique($data['duties'] ?? [])),
            'nomor_sk' => $data['nomor_sk'] ?? null,
            'tmt' => $data['tmt'] ?? null,
            'tst' => $data['tst'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'status' => 'active',
            'notes' => $data['notes'] ?? null,
            'updated_by_id' => $actorId,
        ];
    }

    /**
     * Sinkronkan jabatan ke penempatan satuan kerja asrama saja. Jabatan
     * global pada kepegawaian tidak diubah agar tidak menggeser peran GTK
     * di satuan kerjanya yang lain.
     */
    private function syncWorkUnitPosition(Dormitory $dormitory, string $targetUserId, ?string $positionId): void
    {
        if (! $dormitory->work_unit_id || ! $positionId) {
            return;
        }

        $position = StructuralPosition::find($positionId);

        if (! $position) {
            return;
        }

        GtkWorkUnit::where('user_id', $targetUserId)
            ->where('work_unit_id', $dormitory->work_unit_id)
            ->update([
                'position_id' => $position->id,
                'jabatan' => $position->name,
            ]);
    }

    /**
     * GTK yang ditampilkan: penghuni satuan kerja asrama, ditambah mereka yang
     * sudah punya penugasan aktif (mis. staf lintas unit), serta Kepala Asrama.
     * Bila satuan kerja asrama belum punya penempatan GTK, jatuh ke seluruh
     * GTK satuan pendidikan agar halaman tidak kosong.
     *
     * @return Collection<int, User>
     */
    private function staffUsers(Dormitory $dormitory): Collection
    {
        $assignedUserIds = DormitoryStaffAssignment::where('dormitory_id', $dormitory->id)
            ->where('status', 'active')
            ->pluck('user_id')
            ->all();

        $relations = ['employment', 'gtkWorkUnits'];

        $workUnitUsers = $dormitory->work_unit_id
            ? User::with($relations)
                ->where('is_active', true)
                ->whereHas('gtkWorkUnits', fn ($query) => $query
                    ->where('work_unit_id', $dormitory->work_unit_id))
                ->get()
            : collect();

        $extraIds = array_filter(array_unique(array_merge(
            $assignedUserIds,
            [$dormitory->head_id],
        )));

        $extraUsers = $extraIds === []
            ? collect()
            : User::with($relations)->where('is_active', true)->whereIn('id', $extraIds)->get();

        $users = $workUnitUsers->merge($extraUsers)->unique('id');

        if ($users->isEmpty() && $dormitory->school_id) {
            $users = User::with($relations)
                ->where('is_active', true)
                ->whereHas('employment', fn ($query) => $query
                    ->where('school_id', $dormitory->school_id))
                ->get();
        }

        return $users->sortBy('name')->take(500)->values();
    }

    /**
     * @return Collection<string, DormitoryStaffAssignment>
     */
    private function assignments(Dormitory $dormitory): Collection
    {
        return DormitoryStaffAssignment::with(['structuralPosition', 'taskTypes'])
            ->where('dormitory_id', $dormitory->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->get()
            ->keyBy('user_id');
    }

    /**
     * Satu baris per GTK: Kepala Asrama lebih dulu, lalu staf lain sesuai abjad.
     *
     * @param  Collection<int, User>  $staffUsers
     * @param  Collection<string, DormitoryStaffAssignment>  $assignments
     * @return Collection<int, array<string, mixed>>
     */
    private function assignmentRows(Dormitory $dormitory, Collection $staffUsers, Collection $assignments): Collection
    {
        $rows = collect();

        if ($dormitory->head) {
            $headAssignment = $assignments->get((string) $dormitory->head_id);

            $rows->push($this->row(
                $dormitory->head,
                $headAssignment,
                'Kepala Asrama',
                true,
            ));
        }

        foreach ($staffUsers as $user) {
            if ((string) $user->id === (string) $dormitory->head_id) {
                continue;
            }

            $assignment = $assignments->get((string) $user->id);

            $rows->push($this->row(
                $user,
                $assignment,
                $assignment?->structuralPosition?->name ?? $user->employment?->jabatan,
                false,
            ));
        }

        return $rows->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(User $user, ?DormitoryStaffAssignment $assignment, ?string $positionName, bool $isHead): array
    {
        return [
            'assignment_id' => $assignment?->id,
            'user' => $user,
            'position_id' => $assignment?->position_id,
            'position_name' => $positionName,
            'shift' => $assignment?->shift,
            'duties' => $assignment?->duties ?? [],
            'task_types' => $assignment?->taskTypes ?? collect(),
            'nomor_sk' => $assignment?->nomor_sk,
            'tmt' => $assignment?->tmt,
            'tst' => $assignment?->tst,
            'notes' => $assignment?->notes,
            'start_date' => $assignment?->start_date,
            'end_date' => $assignment?->end_date,
            'is_head' => $isHead,
            'is_unassigned' => $assignment === null && ! $isHead,
        ];
    }
}
