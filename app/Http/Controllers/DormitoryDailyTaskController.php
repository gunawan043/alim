<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryDailyTask;
use App\Models\DormitoryDailyTaskLog;
use App\Models\DormitoryStaffAssignment;
use App\Models\RoomSupervisor;
use App\Services\Boarding\DormitoryPolicyAccess;
use App\Services\Dormitory\DormitoryAudit;
use App\Support\DormitoryShift;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DormitoryDailyTaskController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $this->authorizeView($request->user(), $dormitory);

        $canManage = $this->canManage($request->user(), $dormitory);
        $canRecord = $this->canRecord($request->user(), $dormitory);

        $selectedDate = now()->toDateString();

        if ($request->filled('date')) {
            try {
                $selectedDate = Carbon::parse($request->input('date'))->toDateString();
            } catch (\Throwable) {
                $selectedDate = now()->toDateString();
            }
        }

        $selectedShift = in_array($request->input('shift'), DormitoryShift::keys(), true)
            ? $request->input('shift')
            : DormitoryShift::current();

        $activeYear = AcademicYear::where('is_active', true)->first();

        $checklistTasks = DormitoryDailyTask::query()
            ->where('dormitory_id', $asramaUuid)
            ->where('shift', $selectedShift)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $logs = DormitoryDailyTaskLog::query()
            ->where('dormitory_id', $asramaUuid)
            ->whereDate('task_date', $selectedDate)
            ->where('shift', $selectedShift)
            ->whereIn('task_id', $checklistTasks->pluck('id'))
            ->get()
            ->keyBy('task_id');

        $allTasks = collect();
        if ($canManage) {
            $allTasks = DormitoryDailyTask::with('creator')
                ->where('dormitory_id', $asramaUuid)
                ->orderByRaw("CASE shift WHEN 'pagi' THEN 1 WHEN 'siang' THEN 2 ELSE 3 END")
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        $todayLogs = DormitoryDailyTaskLog::query()
            ->where('dormitory_id', $asramaUuid)
            ->whereDate('task_date', $selectedDate)
            ->get();

        $stats = [
            'active_tasks' => DormitoryDailyTask::where('dormitory_id', $asramaUuid)->where('is_active', true)->count(),
            'shift_tasks' => $checklistTasks->count(),
            'shift_done' => $logs->where('is_done', true)->count(),
            'day_done' => $todayLogs->where('is_done', true)->count(),
            'day_total' => max($todayLogs->count(), DormitoryDailyTask::where('dormitory_id', $asramaUuid)->where('is_active', true)->count()),
        ];

        return view('dormitory.daily-tasks.index', compact(
            'dormitory',
            'userId',
            'activeYear',
            'canManage',
            'canRecord',
            'selectedDate',
            'selectedShift',
            'checklistTasks',
            'logs',
            'allTasks',
            'stats'
        ));
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat membuat tugas harian.');

        return view('dormitory.daily-tasks.create', [
            'dormitory' => $dormitory,
            'userId' => $userId,
            'shifts' => DormitoryShift::options(),
            'defaultShift' => DormitoryShift::current(),
        ]);
    }

    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat membuat tugas harian.');

        $data = $this->validatedTask($request);
        $task = DormitoryDailyTask::create($data + [
            'dormitory_id' => $asramaUuid,
            'academic_year_id' => AcademicYear::where('is_active', true)->value('id'),
            'created_by' => $request->user()->id,
        ]);

        DormitoryAudit::log('DORMITORY_DAILY_TASK_CREATED', 'dormitory_daily_tasks', $task->id);

        return redirect()
            ->route('user.asrama.daily-tasks.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid,
                'shift' => $task->shift,
            ])
            ->with('success', 'Tugas harian berhasil dibuat.');
    }

    public function edit(Request $request, string $userId, string $asramaUuid, string $taskId)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat mengubah tugas harian.');

        $task = DormitoryDailyTask::where('dormitory_id', $asramaUuid)->findOrFail($taskId);

        return view('dormitory.daily-tasks.edit', [
            'dormitory' => $dormitory,
            'userId' => $userId,
            'task' => $task,
            'shifts' => DormitoryShift::options(),
        ]);
    }

    public function update(Request $request, string $userId, string $asramaUuid, string $taskId)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat mengubah tugas harian.');

        $task = DormitoryDailyTask::where('dormitory_id', $asramaUuid)->findOrFail($taskId);
        $task->update($this->validatedTask($request));

        DormitoryAudit::log('DORMITORY_DAILY_TASK_UPDATED', 'dormitory_daily_tasks', $task->id);

        return redirect()
            ->route('user.asrama.daily-tasks.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid,
                'shift' => $task->shift,
            ])
            ->with('success', 'Tugas harian diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $asramaUuid, string $taskId)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat menghapus tugas harian.');

        $task = DormitoryDailyTask::where('dormitory_id', $asramaUuid)->findOrFail($taskId);
        $task->delete();

        DormitoryAudit::log('DORMITORY_DAILY_TASK_DELETED', 'dormitory_daily_tasks', $task->id);

        return back()->with('success', 'Tugas harian dihapus. Riwayat checklist sebelumnya tetap tersimpan.');
    }

    public function toggle(Request $request, string $userId, string $asramaUuid, string $taskId)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canManage($request->user(), $dormitory), 403, 'Hanya Kepala/Wakil Asrama yang dapat mengubah status tugas harian.');

        $task = DormitoryDailyTask::where('dormitory_id', $asramaUuid)->findOrFail($taskId);
        $task->update(['is_active' => ! $task->is_active]);

        DormitoryAudit::log('DORMITORY_DAILY_TASK_TOGGLED', 'dormitory_daily_tasks', $task->id);

        return back()->with('success', $task->is_active ? 'Tugas diaktifkan.' : 'Tugas dinonaktifkan.');
    }

    /**
     * Simpan checklist satu shift untuk satu tanggal.
     */
    public function storeChecklist(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        abort_unless($this->canRecord($request->user(), $dormitory), 403, 'Hanya staf asrama atau wali kamar yang dapat mengisi checklist.');

        $data = $request->validate([
            'task_date' => 'required|date',
            'shift' => 'required|in:'.implode(',', DormitoryShift::keys()),
            'done' => 'nullable|array',
            'done.*' => 'boolean',
            'notes' => 'nullable|array',
            'notes.*' => 'nullable|string|max:500',
        ]);

        $date = Carbon::parse($data['task_date'])->toDateString();
        $shift = $data['shift'];

        $tasks = DormitoryDailyTask::query()
            ->where('dormitory_id', $asramaUuid)
            ->where('shift', $shift)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($tasks->isEmpty()) {
            return back()->with('warning', 'Belum ada tugas aktif untuk shift ini.');
        }

        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $done = 0;
        $pending = 0;

        DB::transaction(function () use ($request, $tasks, $asramaUuid, $activeYearId, $date, $shift, $data, &$done, &$pending) {
            foreach ($tasks as $task) {
                $isDone = (bool) ($data['done'][$task->id] ?? false);
                $isDone ? $done++ : $pending++;

                DormitoryDailyTaskLog::updateOrCreate(
                    ['task_id' => $task->id, 'task_date' => $date],
                    [
                        'dormitory_id' => $asramaUuid,
                        'academic_year_id' => $activeYearId,
                        'shift' => $shift,
                        'task_name' => $task->name,
                        'is_done' => $isDone,
                        'notes' => $data['notes'][$task->id] ?? null,
                        'checked_by' => $isDone ? $request->user()->id : null,
                        'checked_at' => $isDone ? now() : null,
                    ]
                );
            }
        });

        DormitoryAudit::log('DORMITORY_DAILY_TASK_CHECKLIST_SAVED', 'dormitory_daily_task_logs', null);

        return redirect()
            ->route('user.asrama.daily-tasks.index', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid,
                'date' => $date,
                'shift' => $shift,
            ])
            ->with('success', "Checklist tersimpan: {$done} selesai, {$pending} belum selesai.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedTask(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'shift' => 'required|in:'.implode(',', DormitoryShift::keys()),
            'description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function authorizeView($user, Dormitory $dormitory): void
    {
        abort_unless(
            $user && ($this->canManage($user, $dormitory) || $this->canRecord($user, $dormitory)),
            403,
            'Anda tidak memiliki akses ke tugas harian asrama ini.'
        );
    }

    private function canManage($user, Dormitory $dormitory): bool
    {
        if (! $user) {
            return false;
        }

        return app(DormitoryPolicyAccess::class)->canManageQuota($user, $dormitory->id);
    }

    private function canRecord($user, Dormitory $dormitory): bool
    {
        if ($this->canManage($user, $dormitory)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if (DormitoryStaffAssignment::active()->forUser($user->id)->forDormitory($dormitory->id)->exists()) {
            return true;
        }

        return RoomSupervisor::active()
            ->where('user_id', $user->id)
            ->where('dormitory_id', $dormitory->id)
            ->exists();
    }
}
