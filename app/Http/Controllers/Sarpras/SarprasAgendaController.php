<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\AssetMaintenanceSchedule;
use App\Models\ChecklistInstance;
use App\Models\StockOpnameSession;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkUnitSarprasOfficer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SarprasAgendaController extends SarprasBaseController
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $isCentral = $this->isUrtLeadership();
        $headedIds = $currentUser->headedWorkUnitIds();
        $teamView = $isCentral || $headedIds !== [];

        $officerQuery = WorkUnitSarprasOfficer::with(['user', 'workUnit'])
            ->active()
            ->when(! $isCentral && $headedIds !== [], fn ($q) => $q->whereIn('work_unit_id', $headedIds));

        $officers = $teamView ? $officerQuery->get() : collect();
        $allowedUserIds = $officers->pluck('user_id')
            ->push($currentUser->id)
            ->unique()
            ->values();

        $selectedUserId = $request->input('user_id', $currentUser->id);

        abort_unless($allowedUserIds->contains($selectedUserId), 403, 'Anda tidak berhak melihat agenda pegawai ini.');

        $selectedUser = User::findOrFail($selectedUserId);
        $users = $teamView
            ? User::whereIn('id', $allowedUserIds)->orderBy('name')->get(['id', 'name'])
            : collect([$currentUser]);

        $tasks = collect();

        WorkOrder::with(['asset.room.building', 'room.building', 'building', 'maintenanceSchedule', 'repairRequest'])
            ->where('assignee_id', $selectedUserId)
            ->whereNotIn('status', ['completed', 'closed', 'cancelled'])
            ->get()
            ->each(function (WorkOrder $order) use ($tasks) {
                $type = $order->maintenance_schedule_id ? 'Perawatan' : ($order->repair_request_id ? 'Perbaikan' : 'Work Order');
                $tasks->push([
                    'date' => $order->scheduled_date ?? $order->created_at,
                    'type' => $type,
                    'title' => $order->scope_of_work,
                    'subtitle' => $order->order_number,
                    'location' => $order->target_location,
                    'status' => ucfirst(str_replace('_', ' ', $order->status)),
                    'tone' => $order->status === 'in_progress' ? 'warning' : 'info',
                    'url' => route('sarpras.teknisi.show', $order->id),
                    'icon' => $type === 'Perbaikan' ? 'ri-tools-line' : 'ri-calendar-check-line',
                ]);
            });

        AssetMaintenanceSchedule::with(['asset.room.building', 'room.building', 'building', 'workUnit'])
            ->where('responsible_user_id', $selectedUserId)
            ->where('is_active', true)
            ->where('unit_approval_status', AssetMaintenanceSchedule::APPROVAL_APPROVED)
            ->get()
            ->each(function (AssetMaintenanceSchedule $schedule) use ($tasks) {
                $tasks->push([
                    'date' => $schedule->next_maintenance_date,
                    'type' => 'Jadwal',
                    'title' => $schedule->maintenance_type,
                    'subtitle' => $schedule->workUnit?->name,
                    'location' => $schedule->asset?->asset_name ?? $schedule->room?->room_name ?? $schedule->building?->building_name ?? '-',
                    'status' => 'Terjadwal',
                    'tone' => $schedule->next_maintenance_date?->isPast() ? 'danger' : 'success',
                    'url' => route('sarpras.pemeliharaan.schedule.show', $schedule->id),
                    'icon' => 'ri-calendar-todo-line',
                ]);
            });

        StockOpnameSession::with(['workUnit', 'room'])
            ->whereIn('status', [StockOpnameSession::STATUS_PLANNED, StockOpnameSession::STATUS_IN_PROGRESS])
            ->whereHas('officers', fn ($q) => $q->where('user_id', $selectedUserId))
            ->get()
            ->each(function (StockOpnameSession $session) use ($tasks) {
                $tasks->push([
                    'date' => $session->scheduled_date,
                    'type' => 'Stock Opname',
                    'title' => $session->title,
                    'subtitle' => $session->session_code,
                    'location' => $session->room?->room_name ?? $session->workUnit?->name ?? '-',
                    'status' => $session->status === 'in_progress' ? 'Berjalan' : 'Direncanakan',
                    'tone' => $session->status === 'in_progress' ? 'warning' : 'secondary',
                    'url' => route('sarpras.stock-opname.show', $session->id),
                    'icon' => 'ri-clipboard-line',
                ]);
            });

        ChecklistInstance::with(['template', 'context'])
            ->where('executor_id', $selectedUserId)
            ->where('status', 'in_progress')
            ->get()
            ->each(function (ChecklistInstance $instance) use ($tasks) {
                $context = $instance->context;
                $tasks->push([
                    'date' => $context?->next_maintenance_date ?? $instance->created_at,
                    'type' => 'Checklist',
                    'title' => $instance->template?->name ?? 'Checklist Pemeliharaan',
                    'subtitle' => $instance->template?->code,
                    'location' => $context instanceof AssetMaintenanceSchedule
                        ? ($context->asset?->asset_name ?? $context->room?->room_name ?? $context->building?->building_name ?? '-')
                        : '-',
                    'status' => 'Berjalan',
                    'tone' => 'info',
                    'url' => route('sarpras.checklist.instance', $instance->id),
                    'icon' => 'ri-clipboard-check-line',
                ]);
            });

        if ($request->filled('type')) {
            $tasks = $tasks->where('type', $request->type);
        }

        $today = today();
        $tasks = $tasks->sortBy(fn ($task) => $task['date']?->timestamp ?? PHP_INT_MAX)->values();

        $stats = [
            'total' => $tasks->count(),
            'overdue' => $tasks->filter(fn ($task) => $task['date'] && $task['date']->lt($today))->count(),
            'today' => $tasks->filter(fn ($task) => $task['date'] && $task['date']->isSameDay($today))->count(),
            'upcoming' => $tasks->filter(fn ($task) => $task['date'] && $task['date']->gt($today))->count(),
        ];

        $types = $tasks->pluck('type')->unique()->sort()->values();

        return view('sarpras.agenda.index', compact(
            'tasks', 'stats', 'users', 'officers', 'selectedUser', 'selectedUserId', 'teamView', 'types'
        ));
    }
}
