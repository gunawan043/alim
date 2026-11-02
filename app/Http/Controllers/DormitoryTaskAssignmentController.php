<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\RoomSupervisor;
use App\Models\User;
use App\Services\Boarding\DormitoryPolicyAccess;
use App\Services\Dormitory\DormitoryAudit;
use App\Support\DormitoryPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DormitoryTaskAssignmentController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $this->authorizeManager($request->user(), $dormitory);

        $assignedRows = $this->assignmentRows($dormitory);
        $assignments = DormitoryStaffAssignment::with(['user.employment'])
            ->where('dormitory_id', $asramaUuid)
            ->where('status', 'active')
            ->orderBy('id')
            ->get()
            ->keyBy('user_id');

        $eligibleUsers = $this->eligibleUsers($dormitory);
        $assignedUserIds = $assignments->keys()->push((string) $dormitory->head_id)->filter()->unique()->all();
        $availableUsers = $eligibleUsers->reject(fn (User $user) => in_array((string) $user->id, $assignedUserIds, true))->values();

        $rows = $assignedRows->concat($this->unassignedRows($availableUsers));

        return view('dormitory.task-assignments.index', [
            'dormitory' => $dormitory,
            'userId' => $userId,
            'rows' => $rows,
            'assignments' => $assignments,
            'availableUsers' => $availableUsers,
            'positions' => DormitoryPosition::assignableOptions(),
            'duties' => DormitoryPosition::duties(),
            'shifts' => DormitoryPosition::shiftOptions(),
            'stats' => [
                'total' => $assignedRows->count(),
                'leaders' => $assignedRows->whereIn('position', [DormitoryPosition::KEPALA, DormitoryPosition::WAKIL])->count(),
                'duties' => $assignedRows->sum(fn (array $row) => count($row['duties'] ?? [])),
            ],
        ]);
    }

    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $this->authorizeManager($request->user(), $dormitory);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'position' => 'required|in:'.implode(',', DormitoryPosition::assignableKeys()),
            'shift' => 'nullable|in:'.implode(',', array_keys(DormitoryPosition::shiftOptions())),
            'duties' => 'nullable|array',
            'duties.*' => 'string|in:'.implode(',', DormitoryPosition::dutyKeys()),
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->assertEligibleUser($dormitory, $data['user_id']);

        $assignment = DormitoryStaffAssignment::withTrashed()
            ->where('user_id', $data['user_id'])
            ->where('dormitory_id', $asramaUuid)
            ->first();

        $payload = $this->payload($data, $request->user()->id);

        if ($assignment) {
            $assignment->restore();
            $assignment->update($payload);
        } else {
            $assignment = DormitoryStaffAssignment::create($payload + [
                'user_id' => $data['user_id'],
                'dormitory_id' => $asramaUuid,
                'assigned_by_id' => $request->user()->id,
                'status' => 'active',
            ]);
        }

        DormitoryAudit::log('DORMITORY_TASK_ASSIGNMENT_SAVED', 'dormitory_staff_assignments', $assignment->id);

        return back()->with('success', 'Pembagian tugas staf asrama berhasil disimpan.');
    }

    public function update(Request $request, string $userId, string $asramaUuid, string $assignmentUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $this->authorizeManager($request->user(), $dormitory);

        $assignment = DormitoryStaffAssignment::where('dormitory_id', $asramaUuid)->findOrFail($assignmentUuid);

        $data = $request->validate([
            'position' => 'required|in:'.implode(',', DormitoryPosition::assignableKeys()),
            'shift' => 'nullable|in:'.implode(',', array_keys(DormitoryPosition::shiftOptions())),
            'duties' => 'nullable|array',
            'duties.*' => 'string|in:'.implode(',', DormitoryPosition::dutyKeys()),
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $assignment->update($this->payload($data, $request->user()->id));

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

        return view('dormitory.task-assignments.print', [
            'dormitory' => $dormitory,
            'userId' => $userId,
            'rows' => $this->assignmentRows($dormitory),
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

        $roomSupervisor = RoomSupervisor::where('user_id', $target->id)
            ->where('dormitory_id', $dormitory->id)
            ->where('status', 'active')
            ->exists();

        if (! $target->employment || (! $sameSchool && ! $sameWorkUnit && ! $alreadyAssigned && ! $roomSupervisor)) {
            abort(422, 'Staf harus berasal dari satuan pendidikan/satuan kerja asrama ini.');
        }
    }

    private function payload(array $data, string $actorId): array
    {
        return [
            'position' => $data['position'],
            'shift' => $data['shift'] ?? null,
            'duties' => array_values(array_unique($data['duties'] ?? [])),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'status' => 'active',
            'notes' => $data['notes'] ?? null,
            'updated_by_id' => $actorId,
        ];
    }

    /**
     * @return Collection<int, User>
     */
    private function eligibleUsers(Dormitory $dormitory): Collection
    {
        $linkedUserIds = RoomSupervisor::where('dormitory_id', $dormitory->id)
            ->where('status', 'active')
            ->pluck('user_id')
            ->merge(
                DormitoryStaffAssignment::where('dormitory_id', $dormitory->id)
                    ->where('status', 'active')
                    ->pluck('user_id')
            )
            ->filter()
            ->unique()
            ->values()
            ->all();

        return User::with(['employment', 'gtkWorkUnits'])
            ->where('is_active', true)
            ->where(function ($query) use ($dormitory, $linkedUserIds) {
                if ($dormitory->school_id) {
                    $query->whereHas('employment', fn ($employment) => $employment
                        ->where('school_id', $dormitory->school_id));
                }

                if ($dormitory->work_unit_id) {
                    $query->orWhereHas('gtkWorkUnits', fn ($workUnit) => $workUnit
                        ->where('work_unit_id', $dormitory->work_unit_id));
                }

                if ($linkedUserIds !== []) {
                    $query->orWhereIn('id', $linkedUserIds);
                }
            })
            ->orderBy('name')
            ->limit(500)
            ->get();
    }

    /**
     * GTK yang sudah terhubung dengan asrama tetapi belum punya
     * pembagian tugas. Ditampilkan agar Kepala bisa langsung menugaskan.
     *
     * @param  Collection<int, User>  $users
     * @return Collection<int, array<string, mixed>>
     */
    private function unassignedRows(Collection $users): Collection
    {
        return $users->map(fn (User $user) => [
            'assignment_id' => null,
            'user' => $user,
            'position' => null,
            'position_label' => 'Belum ditugaskan',
            'shift' => null,
            'duties' => [],
            'notes' => null,
            'start_date' => null,
            'end_date' => null,
            'is_head' => false,
            'is_unassigned' => true,
        ])->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function assignmentRows(Dormitory $dormitory): Collection
    {
        $assignments = DormitoryStaffAssignment::with(['user.employment'])
            ->where('dormitory_id', $dormitory->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        $rows = collect();

        if ($dormitory->head) {
            $headAssignment = $assignments->firstWhere('user_id', $dormitory->head_id);

            $rows->push([
                'assignment_id' => $headAssignment?->id,
                'user' => $dormitory->head,
                'position' => DormitoryPosition::KEPALA,
                'position_label' => DormitoryPosition::label(DormitoryPosition::KEPALA),
                'shift' => $headAssignment?->shift ?? 'semua',
                'duties' => $headAssignment?->duties ?? [],
                'notes' => $headAssignment?->notes,
                'start_date' => $headAssignment?->start_date,
                'end_date' => $headAssignment?->end_date,
                'is_head' => true,
            ]);
        }

        foreach ($assignments as $assignment) {
            if ((string) $assignment->user_id === (string) $dormitory->head_id) {
                continue;
            }

            $position = $assignment->position ?: DormitoryPosition::fromEmployment($assignment->user?->employment?->jabatan);

            $rows->push([
                'assignment_id' => $assignment->id,
                'user' => $assignment->user,
                'position' => $position,
                'position_label' => DormitoryPosition::label($position),
                'shift' => $assignment->shift,
                'duties' => $assignment->duties ?? [],
                'notes' => $assignment->notes,
                'start_date' => $assignment->start_date,
                'end_date' => $assignment->end_date,
                'is_head' => false,
            ]);
        }

        return $rows;
    }
}
