<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Requests\Sarpras\MaintenanceLogStoreRequest;
use App\Http\Requests\Sarpras\MaintenanceScheduleStoreRequest;
use App\Http\Requests\Sarpras\MaintenanceScheduleUpdateRequest;
use App\Models\Asset;
use App\Models\AssetBuilding;
use App\Models\AssetMaintenanceLog;
use App\Models\AssetMaintenanceSchedule;
use App\Models\AssetRoom;
use App\Models\ChecklistInstance;
use App\Models\MaintenanceHistory;
use App\Models\WorkOrder;
use App\Models\School;
use App\Models\User;
use App\Services\Sarpras\AssetEventLogger;
use App\Services\SarprasCacheInvalidator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SarprasMaintenanceController extends SarprasBaseController
{
    public function __construct(
        protected AssetEventLogger $eventLogger,
        protected SarprasCacheInvalidator $cacheInvalidator,
    ) {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }

    // ========================================================================
    // JADWAL PEMELIHARAAN
    // ========================================================================

    public function scheduleIndex(Request $request)
    {
        $query = AssetMaintenanceSchedule::with(['asset', 'building', 'room', 'responsibleUser', 'workUnit']);

        if (! $this->isCentralSarpras() && ! $this->canViewAll($request)) {
            $query = $this->scopeToAccessibleWorkUnits($query, $request);
        } elseif ($request->attributes->get('schoolContextId') && ! $this->canViewAll($request)) {
            $query->where('school_id', $request->attributes->get('schoolContextId'));
        }

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->frequency);
        }
        if ($request->filled('unit_approval_status')) {
            $query->where('unit_approval_status', $request->unit_approval_status);
        }
        if ($request->filled('work_unit_id')) {
            $query->where('work_unit_id', $request->work_unit_id);
        }
        if ($request->filled('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }
        if ($request->filled('status')) {
            if ($request->status === 'overdue') {
                $query->where('is_active', true)->whereDate('next_maintenance_date', '<', Carbon::today());
            } elseif ($request->status === 'upcoming') {
                $query->where('is_active', true)->whereDate('next_maintenance_date', '>=', Carbon::today());
            }
        }

        $stats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('unit_approval_status', AssetMaintenanceSchedule::APPROVAL_PENDING)->count(),
            'overdue' => (clone $query)->where('is_active', true)->whereDate('next_maintenance_date', '<', Carbon::today())->count(),
            'approved' => (clone $query)->where('unit_approval_status', AssetMaintenanceSchedule::APPROVAL_APPROVED)->count(),
        ];

        $schedules = $query->orderBy('next_maintenance_date')->paginate(15)->withQueryString();
        $schools = $this->canViewAll($request) ? School::orderBy('name')->get() : collect();
        $workUnits = \App\Models\WorkUnit::query()
            ->when(! $this->isCentralSarpras(), fn ($q) => $q->whereIn('id', $this->accessibleWorkUnitIds()))
            ->orderBy('name')->get();
        $canManage = $this->isCentralSarpras();

        return view('sarpras.pemeliharaan.schedule.index', compact('schedules', 'schools', 'stats', 'workUnits', 'canManage'));
    }

    public function scheduleTimeline(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        if ($year < 2000 || $year > 2100) {
            $year = now()->year;
        }

        $query = AssetMaintenanceSchedule::with(['asset', 'building', 'room', 'workUnit', 'responsibleUser'])
            ->whereYear('next_maintenance_date', $year)
            ->where('is_active', true);

        if (! $this->isCentralSarpras() && ! $this->canViewAll($request)) {
            $query = $this->scopeToAccessibleWorkUnits($query, $request);
        } elseif ($request->attributes->get('schoolContextId') && ! $this->canViewAll($request)) {
            $query->where('school_id', $request->attributes->get('schoolContextId'));
        }

        if ($request->filled('work_unit_id')) {
            $query->where('work_unit_id', $request->work_unit_id);
        }

        $schedules = $query->orderBy('next_maintenance_date')->get();
        $byMonth = $schedules->groupBy(fn ($schedule) => (int) $schedule->next_maintenance_date?->month);
        $workUnits = \App\Models\WorkUnit::query()
            ->when(! $this->isCentralSarpras(), fn ($q) => $q->whereIn('id', $this->accessibleWorkUnitIds()))
            ->orderBy('name')->get();
        $canManage = $this->isCentralSarpras();

        return view('sarpras.pemeliharaan.schedule.timeline', compact('schedules', 'byMonth', 'year', 'workUnits', 'canManage'));
    }

    public function scheduleCreate(Request $request)
    {
        $this->authorizeCentralSarpras();
        $schoolId = $request->attributes->get('schoolContextId');

        $assets = Asset::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('asset_name')->get();

        $buildings = AssetBuilding::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('building_name')->get();

        $rooms = AssetRoom::with('building')->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')->get();

        $users = User::query()->where('is_active', true)->orderBy('name')->get();

        return view('sarpras.pemeliharaan.schedule.create', compact('assets', 'buildings', 'rooms', 'users'));
    }

    public function scheduleStore(MaintenanceScheduleStoreRequest $request)
    {
        $this->authorizeCentralSarpras();
        $validated = $request->validated();

        if (! empty($validated['asset_id'])) {
            $asset = Asset::find($validated['asset_id']);
            $this->authorizeWorkUnitAccess($asset->work_unit_id, $request);
            $validated['work_unit_id'] = $asset->work_unit_id;
            $validated['school_id'] = $asset->school_id;
        } elseif (! empty($validated['building_id'])) {
            $building = AssetBuilding::find($validated['building_id']);
            $validated['work_unit_id'] = $building->work_unit_id;
            $validated['school_id'] = $building->school_id;
        } elseif (! empty($validated['room_id'])) {
            $room = AssetRoom::find($validated['room_id']);
            $validated['work_unit_id'] = $room->work_unit_id;
            $validated['school_id'] = $room->school_id;
        }

        $validated['is_active'] = true;
        $validated['created_by'] = auth()->id();
        $validated['reminder_days_before'] = $validated['reminder_days_before'] ?? 7;
        $validated['unit_approval_status'] = AssetMaintenanceSchedule::APPROVAL_PENDING;
        $validated['unit_approved_by'] = null;
        $validated['unit_approved_at'] = null;

        unset($validated['target_type']);

        $schedule = DB::transaction(function () use ($validated) {
            $schedule = AssetMaintenanceSchedule::create($validated);

            $targetAsset = ! empty($validated['asset_id']) ? Asset::find($validated['asset_id']) : null;
            if ($targetAsset) {
                $this->eventLogger->log($targetAsset, 'maintenance_scheduled', [
                    'schedule_id' => $schedule->id,
                    'frequency' => $schedule->frequency,
                    'next_maintenance_date' => $schedule->next_maintenance_date?->toDateString(),
                    'responsible_user_id' => $schedule->responsible_user_id,
                    'created_by' => auth()->id(),
                ], auth()->id());
            }

            return $schedule;
        });

        $this->cacheInvalidator->invalidateMaintenance();
        $this->cacheInvalidator->invalidateAll();

        return redirect()->route('sarpras.pemeliharaan.schedule.index')
            ->with('success', 'Jadwal pemeliharaan dikirim ke satuan kerja untuk disetujui.');
    }

    public function scheduleShow(Request $request, string $id)
    {
        $schedule = AssetMaintenanceSchedule::with(['asset', 'building', 'room', 'responsibleUser', 'logs', 'unitApprover', 'workUnit'])
            ->findOrFail($id);
        $this->authorizeMaintenanceAccess($schedule, $request);

        $canManage = $this->isCentralSarpras();
        $canApprove = $this->canApproveSchedule($schedule, $request);

        $checklistInstances = ChecklistInstance::with(['template', 'executor'])
            ->where('context_type', AssetMaintenanceSchedule::class)
            ->where('context_id', $schedule->id)
            ->latest()
            ->get();

        $workOrders = WorkOrder::with(['assignee'])
            ->where('maintenance_schedule_id', $schedule->id)
            ->latest()
            ->get();

        $technicians = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('sarpras.pemeliharaan.schedule.show', compact('schedule', 'canManage', 'canApprove', 'checklistInstances', 'workOrders', 'technicians'));
    }

    public function scheduleEdit(Request $request, string $id)
    {
        $this->authorizeCentralSarpras();
        $schedule = AssetMaintenanceSchedule::findOrFail($id);
        $this->authorizeMaintenanceAccess($schedule, $request);

        $schoolId = $request->attributes->get('schoolContextId');

        $assets = Asset::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('asset_name')->get();

        $buildings = AssetBuilding::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('building_name')->get();

        $rooms = AssetRoom::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')->get();

        $users = User::query()->whereHas('roles')->orderBy('name')->get();

        return view('sarpras.pemeliharaan.schedule.edit', compact('schedule', 'assets', 'buildings', 'rooms', 'users'));
    }

    public function scheduleUpdate(MaintenanceScheduleUpdateRequest $request, string $id)
    {
        $schedule = AssetMaintenanceSchedule::findOrFail($id);
        $this->authorizeMaintenanceAccess($schedule, $request);

        $this->authorizeCentralSarpras();
        $validated = $request->validated();

        $validated['is_active'] = $request->boolean('is_active', true);

        $dateChanged = ! empty($validated['next_maintenance_date'])
            && (string) $validated['next_maintenance_date'] !== $schedule->next_maintenance_date?->toDateString();

        if ($dateChanged) {
            $validated['unit_approval_status'] = AssetMaintenanceSchedule::APPROVAL_PENDING;
            $validated['unit_approved_by'] = null;
            $validated['unit_approved_at'] = null;
            $validated['unit_approval_note'] = null;
            $validated['unit_proposed_date'] = null;
        }

        $schedule->update($validated);

        $this->cacheInvalidator->invalidateMaintenance();
        $this->cacheInvalidator->invalidateAll();

        return redirect()->route('sarpras.pemeliharaan.schedule.show', $schedule->id)
            ->with('success', $dateChanged
                ? 'Jadwal diperbarui dan dikirim ulang ke satuan kerja untuk disetujui.'
                : 'Jadwal pemeliharaan berhasil diperbarui.');
    }

    public function scheduleDestroy(Request $request, string $id)
    {
        $this->authorizeCentralSarpras();
        $schedule = AssetMaintenanceSchedule::findOrFail($id);
        $this->authorizeMaintenanceAccess($schedule, $request);

        $schedule->delete();

        $this->cacheInvalidator->invalidateMaintenance();
        $this->cacheInvalidator->invalidateAll();

        return redirect()->route('sarpras.pemeliharaan.schedule.index')
            ->with('success', 'Jadwal pemeliharaan berhasil dihapus.');
    }

    public function storeScheduleWorkOrder(Request $request, string $id)
    {
        $this->authorizeCentralSarpras();

        $schedule = AssetMaintenanceSchedule::with(['asset', 'room', 'building'])->findOrFail($id);
        abort_unless(
            $schedule->unit_approval_status === AssetMaintenanceSchedule::APPROVAL_APPROVED,
            422,
            'Jadwal harus disetujui satuan kerja sebelum work order dibuat.'
        );
        abort_unless(
            $schedule->asset_id || $schedule->room_id || $schedule->building_id,
            422,
            'Jadwal belum memiliki target aset, ruang, atau gedung.'
        );

        $validated = $request->validate([
            'assignee_id' => 'required|uuid|exists:users,id',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'type' => 'required|in:internal,vendor',
            'scope_of_work' => 'required|string|max:2000',
            'estimated_cost' => 'nullable|numeric|min:0',
        ]);

        $workOrder = DB::transaction(function () use ($schedule, $validated) {
            $workOrder = WorkOrder::create([
                'repair_request_id' => null,
                'maintenance_schedule_id' => $schedule->id,
                'asset_id' => $schedule->asset_id,
                'room_id' => $schedule->room_id,
                'building_id' => $schedule->building_id,
                'assignee_id' => $validated['assignee_id'],
                'created_by' => auth()->id(),
                'type' => $validated['type'],
                'scope_of_work' => $validated['scope_of_work'],
                'scheduled_date' => $validated['scheduled_date'],
                'status' => 'dispatched',
                'total_cost' => $validated['estimated_cost'] ?? 0,
            ]);

            $schedule->update(['status' => 'assigned']);

            if ($schedule->asset) {
                $this->eventLogger->logWorkOrderCreated($schedule->asset, $workOrder->order_number, auth()->id());
            }

            return $workOrder;
        });

        try {
            app(\App\Services\Sarpras\SarprasNotificationService::class)->dispatchWorkOrderCreated($workOrder);
        } catch (\Throwable $e) {
            Log::warning('Notifikasi work order perawatan gagal: '.$e->getMessage());
        }

        return back()->with('success', 'Work order '.$workOrder->order_number.' dibuat dan ditugaskan ke teknisi.');
    }

    public function approveSchedule(Request $request, string $id)
    {
        $schedule = AssetMaintenanceSchedule::findOrFail($id);
        $this->authorizeScheduleApproval($schedule, $request);

        $validated = $request->validate([
            'unit_approval_note' => 'nullable|string|max:1000',
        ]);

        $schedule->update([
            'unit_approval_status' => AssetMaintenanceSchedule::APPROVAL_APPROVED,
            'unit_approved_by' => auth()->id(),
            'unit_approved_at' => now(),
            'unit_approval_note' => $validated['unit_approval_note'] ?? null,
            'unit_proposed_date' => null,
        ]);

        $this->logScheduleEvent($schedule, 'maintenance_approved_by_unit');

        return back()->with('success', 'Jadwal pemeliharaan disetujui satuan kerja.');
    }

    public function requestReschedule(Request $request, string $id)
    {
        $schedule = AssetMaintenanceSchedule::findOrFail($id);
        $this->authorizeScheduleApproval($schedule, $request);

        $validated = $request->validate([
            'unit_proposed_date' => 'required|date|after_or_equal:today',
            'unit_approval_note' => 'required|string|max:1000',
        ]);

        $schedule->update([
            'unit_approval_status' => AssetMaintenanceSchedule::APPROVAL_RESCHEDULE,
            'unit_approved_by' => auth()->id(),
            'unit_approved_at' => now(),
            'unit_approval_note' => $validated['unit_approval_note'],
            'unit_proposed_date' => $validated['unit_proposed_date'],
        ]);

        $this->logScheduleEvent($schedule, 'maintenance_reschedule_requested');

        return back()->with('success', 'Permintaan jadwal ulang dikirim ke Unit Rumah Tangga.');
    }

    public function rejectSchedule(Request $request, string $id)
    {
        $schedule = AssetMaintenanceSchedule::findOrFail($id);
        $this->authorizeScheduleApproval($schedule, $request);

        $validated = $request->validate([
            'unit_approval_note' => 'required|string|max:1000',
        ]);

        $schedule->update([
            'unit_approval_status' => AssetMaintenanceSchedule::APPROVAL_REJECTED,
            'unit_approved_by' => auth()->id(),
            'unit_approved_at' => now(),
            'unit_approval_note' => $validated['unit_approval_note'],
        ]);

        $this->logScheduleEvent($schedule, 'maintenance_rejected_by_unit');

        return back()->with('success', 'Jadwal pemeliharaan ditolak satuan kerja.');
    }

    private function canApproveSchedule(AssetMaintenanceSchedule $schedule, Request $request): bool
    {
        if ($this->isCentralSarpras()) {
            return true;
        }

        return $schedule->work_unit_id
            && in_array($schedule->work_unit_id, $this->accessibleWorkUnitIds(), true);
    }

    private function authorizeScheduleApproval(AssetMaintenanceSchedule $schedule, Request $request): void
    {
        abort_unless($this->canApproveSchedule($schedule, $request), 403, 'Anda tidak berhak menyetujui jadwal satuan kerja ini.');
    }

    private function logScheduleEvent(AssetMaintenanceSchedule $schedule, string $event): void
    {
        try {
            if ($schedule->asset_id) {
                $this->eventLogger->log(Asset::find($schedule->asset_id), $event, [
                    'schedule_id' => $schedule->id,
                    'unit_approval_status' => $schedule->unit_approval_status,
                    'unit_proposed_date' => $schedule->unit_proposed_date?->toDateString(),
                    'note' => $schedule->unit_approval_note,
                    'actor_id' => auth()->id(),
                ], auth()->id());
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat event jadwal pemeliharaan: '.$e->getMessage());
        }
    }

    // ========================================================================
    // RIWAYAT PERAWATAN (LOG)
    // ========================================================================

    public function logIndex(Request $request)
    {
        $query = AssetMaintenanceLog::with(['asset', 'building', 'room', 'performer', 'creator']);

        if (! $this->isCentralSarpras() && ! $this->canViewAll($request)) {
            $query = $this->scopeToAccessibleWorkUnits($query, $request);
        } elseif ($request->attributes->get('schoolContextId') && ! $this->canViewAll($request)) {
            $query->where('school_id', $request->attributes->get('schoolContextId'));
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('maintenance_type', 'like', "%{$s}%");
        }

        $logs = $query->orderBy('maintenance_date', 'desc')->paginate(15)->withQueryString();
        $schools = $this->canViewAll($request) ? School::orderBy('name')->get() : collect();

        return view('sarpras.pemeliharaan.log.index', compact('logs', 'schools'));
    }

    public function logCreate(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $scheduleQuery = AssetMaintenanceSchedule::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['asset', 'building', 'room']);
        if (! $this->isCentralSarpras()) {
            $scheduleQuery = $this->scopeToAccessibleWorkUnits($scheduleQuery, $request);
        }
        $schedules = $scheduleQuery->orderBy('next_maintenance_date')->get();

        $assetQuery = Asset::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        if (! $this->isCentralSarpras()) {
            $assetQuery = $this->scopeToResponsibility($assetQuery, $request);
        }
        $assets = $assetQuery->orderBy('asset_name')->get();

        $buildings = AssetBuilding::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('building_name')->get();

        $rooms = AssetRoom::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer(), fn ($q) => $q->whereIn('id', $this->responsibleRoomIds()))
            ->orderBy('room_name')->get();

        $users = User::query()->whereHas('roles')->orderBy('name')->get();

        return view('sarpras.pemeliharaan.log.create', compact('schedules', 'assets', 'buildings', 'rooms', 'users'));
    }

    public function logStore(MaintenanceLogStoreRequest $request)
    {
        $validated = $request->validated();

        if (! empty($validated['schedule_id'])) {
            $schedule = AssetMaintenanceSchedule::find($validated['schedule_id']);
            if ($schedule) {
                $this->authorizeWorkUnitAccess($schedule->work_unit_id, $request);
            }
        }

        if (! empty($validated['asset_id'])) {
            $asset = Asset::find($validated['asset_id']);
            $this->authorizeWorkUnitAccess($asset->work_unit_id, $request);
            $validated['work_unit_id'] = $asset->work_unit_id;
            $validated['school_id'] = $asset->school_id;
        } elseif (! empty($validated['building_id'])) {
            $building = AssetBuilding::find($validated['building_id']);
            $validated['work_unit_id'] = $building->work_unit_id;
            $validated['school_id'] = $building->school_id;
        } elseif (! empty($validated['room_id'])) {
            $room = AssetRoom::find($validated['room_id']);
            $validated['work_unit_id'] = $room->work_unit_id;
            $validated['school_id'] = $room->school_id;
        }

        $validated['created_by'] = auth()->id();
        $validated['handled_by'] = $validated['performed_by'] ?? auth()->id();
        $validated['description'] = $validated['work_description'] ?? $validated['maintenance_type'];
        $validated['cost'] = $validated['actual_cost'] ?? 0;
        unset($validated['target_type']);

        $log = DB::transaction(function () use ($validated) {
            $nextDate = null;

            if (! empty($validated['schedule_id'])) {
                $schedule = AssetMaintenanceSchedule::find($validated['schedule_id']);
                if ($schedule) {
                    $nextDate = $this->calculateNextMaintenanceDate($validated['maintenance_date'], $schedule->frequency);
                    $validated['next_due_date'] = $nextDate;
                }
            }

            $log = AssetMaintenanceLog::create($validated);

            if (! empty($validated['schedule_id']) && $nextDate) {
                AssetMaintenanceSchedule::whereKey($validated['schedule_id'])->update([
                    'last_maintenance_date' => $validated['maintenance_date'],
                    'next_maintenance_date' => $nextDate,
                    'unit_approval_status' => AssetMaintenanceSchedule::APPROVAL_APPROVED,
                ]);
            }

            if (! empty($validated['asset_id']) && ! empty($validated['condition_after'])) {
                Asset::whereKey($validated['asset_id'])->update([
                    'condition' => $validated['condition_after'],
                    'last_condition_update' => today(),
                ]);
            }

            // Proyeksi ke maintenance_histories agar passport/QR menampilkan riwayat unit aset.
            $targetAsset = ! empty($validated['asset_id']) ? Asset::find($validated['asset_id']) : null;
            if ($targetAsset) {
                MaintenanceHistory::create([
                    'asset_id' => $targetAsset->id,
                    'maintenance_type' => $validated['maintenance_type'],
                    'performed_date' => $validated['maintenance_date'],
                    'performed_by_name' => ! empty($validated['performed_by']) ? User::find($validated['performed_by'])?->name : auth()->user()?->name,
                    'condition_before' => $validated['condition_before'] ?? null,
                    'condition_after' => $validated['condition_after'] ?? null,
                    'work_description' => trim(($validated['work_description'] ?? '').($validated['parts_replaced'] ?? '' ? ' | Ganti: '.$validated['parts_replaced'] : '')),
                    'cost' => $validated['actual_cost'] ?? 0,
                    'next_due_date' => $validated['next_due_date'] ?? null,
                ]);

                $this->eventLogger->log($targetAsset, 'maintenance_completed', [
                    'log_id' => $log->id,
                    'condition_before' => $validated['condition_before'] ?? null,
                    'condition_after' => $validated['condition_after'] ?? null,
                    'performed_by' => $validated['performed_by'] ?? auth()->id(),
                    'cost' => $validated['cost'] ?? null,
                    'maintenance_date' => $validated['maintenance_date'] ?? now()->toDateString(),
                    'maintenance_type' => $validated['maintenance_type'] ?? null,
                ], auth()->id());
            }

            return $log;
        });

        $this->cacheInvalidator->invalidateMaintenance();
        $this->cacheInvalidator->invalidateAll();

        return redirect()->route('sarpras.pemeliharaan.log.index')
            ->with('success', 'Riwayat perawatan berhasil ditambahkan.');
    }

    public function logShow(Request $request, string $id)
    {
        $log = AssetMaintenanceLog::with(['schedule', 'asset', 'building', 'room', 'performer', 'creator'])
            ->findOrFail($id);

        return view('sarpras.pemeliharaan.log.show', compact('log'));
    }

    private function calculateNextMaintenanceDate($lastDate, $frequency)
    {
        $date = Carbon::parse($lastDate);

        return match ($frequency) {
            'harian' => $date->addDay(),
            'mingguan' => $date->addWeek(),
            'bulanan' => $date->addMonth(),
            'triwulan' => $date->addMonths(3),
            'semester' => $date->addMonths(6),
            'tahunan' => $date->addYear(),
            default => $date->addMonth(),
        };
    }
}
