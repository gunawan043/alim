<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\AssetMaintenanceSchedule;
use App\Models\AssetMaintenanceLog;
use App\Models\Asset;
use App\Models\AssetBuilding;
use App\Models\AssetRoom;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SarprasMaintenanceController extends SarprasBaseController
{
    public function __construct()
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }


    // ========================================================================
    // JADWAL PEMELIHARAAN
    // ========================================================================

    public function scheduleIndex(Request $request)
    {
        $query = AssetMaintenanceSchedule::with(['asset', 'building', 'room', 'responsibleUser']);

        if (!$this->canViewAll($request)) {
            $query = $this->scopeToSchool($request, $query);
        }

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->frequency);
        }
        if ($request->filled('status')) {
            if ($request->status === 'overdue') {
                $query->where('is_active', true)->whereDate('next_maintenance_date', '<', Carbon::today());
            } elseif ($request->status === 'upcoming') {
                $query->where('is_active', true)->whereDate('next_maintenance_date', '>=', Carbon::today());
            }
        }

        $schedules = $query->orderBy('next_maintenance_date')->paginate(15)->withQueryString();
        $schools = $this->canViewAll($request) ? School::orderBy('name')->get() : collect();

        return view('sarpras.pemeliharaan.schedule.index', compact('schedules', 'schools'));
    }

    public function scheduleCreate(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $assets = Asset::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('asset_name')->get();

        $buildings = AssetBuilding::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('building_name')->get();

        $rooms = AssetRoom::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')->get();

        $users = User::whereHas('roles')->orderBy('name')->get();

        return view('sarpras.pemeliharaan.schedule.create', compact('assets', 'buildings', 'rooms', 'users'));
    }

    public function scheduleStore(Request $request)
    {
        $validated = $request->validate([
            'target_type'            => 'required|in:asset,building,room',
            'asset_id'              => 'required_if:target_type,asset|nullable|exists:assets,id',
            'building_id'           => 'required_if:target_type,building|nullable|exists:asset_buildings,id',
            'room_id'               => 'required_if:target_type,room|nullable|exists:asset_rooms,id',
            'maintenance_type'      => 'required|string|max:100',
            'frequency'             => 'required|in:' . implode(',', AssetMaintenanceSchedule::FREQUENCY_OPTIONS),
            'next_maintenance_date' => 'required|date',
            'responsible_user_id'    => 'nullable|exists:users,id',
            'vendor_name'           => 'nullable|string|max:191',
            'estimated_cost'        => 'nullable|numeric|min:0',
            'reminder_days_before'  => 'nullable|integer|min:1|max:90',
            'notes'                 => 'nullable|string',
        ]);

        if (!empty($validated['asset_id'])) {
            $asset = Asset::find($validated['asset_id']);
            $validated['work_unit_id'] = $asset->work_unit_id;
            $validated['school_id'] = $asset->school_id;
        } elseif (!empty($validated['building_id'])) {
            $building = AssetBuilding::find($validated['building_id']);
            $validated['work_unit_id'] = $building->work_unit_id;
            $validated['school_id'] = $building->school_id;
        } elseif (!empty($validated['room_id'])) {
            $room = AssetRoom::find($validated['room_id']);
            $validated['work_unit_id'] = $room->work_unit_id;
            $validated['school_id'] = $room->school_id;
        }

        $validated['is_active'] = true;
        $validated['created_by'] = auth()->id();
        $validated['reminder_days_before'] = $validated['reminder_days_before'] ?? 7;

        unset($validated['target_type']);

        AssetMaintenanceSchedule::create($validated);

        return redirect()->route('sarpras.pemeliharaan.schedule.index')
            ->with('success', 'Jadwal pemeliharaan berhasil ditambahkan.');
    }

    public function scheduleShow(Request $request, string $id)
    {
        $schedule = AssetMaintenanceSchedule::with(['asset', 'building', 'room', 'responsibleUser', 'logs'])
            ->findOrFail($id);
        $this->authorizeMaintenanceAccess($schedule, $request);

        return view('sarpras.pemeliharaan.schedule.show', compact('schedule'));
    }

    public function scheduleEdit(Request $request, string $id)
    {
        $schedule = AssetMaintenanceSchedule::findOrFail($id);
        $this->authorizeMaintenanceAccess($schedule, $request);

        $schoolId = $request->attributes->get('schoolContextId');

        $assets = Asset::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('asset_name')->get();

        $buildings = AssetBuilding::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('building_name')->get();

        $rooms = AssetRoom::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')->get();

        $users = User::whereHas('roles')->orderBy('name')->get();

        return view('sarpras.pemeliharaan.schedule.edit', compact('schedule', 'assets', 'buildings', 'rooms', 'users'));
    }

    public function scheduleUpdate(Request $request, string $id)
    {
        $schedule = AssetMaintenanceSchedule::findOrFail($id);
        $this->authorizeMaintenanceAccess($schedule, $request);

        $validated = $request->validate([
            'maintenance_type'       => 'required|string|max:100',
            'frequency'             => 'required|in:' . implode(',', AssetMaintenanceSchedule::FREQUENCY_OPTIONS),
            'next_maintenance_date' => 'required|date',
            'responsible_user_id'   => 'nullable|exists:users,id',
            'vendor_name'          => 'nullable|string|max:191',
            'estimated_cost'       => 'nullable|numeric|min:0',
            'reminder_days_before' => 'nullable|integer|min:1|max:90',
            'is_active'            => 'boolean',
            'notes'                => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $schedule->update($validated);

        return redirect()->route('sarpras.pemeliharaan.schedule.show', $schedule->id)
            ->with('success', 'Jadwal pemeliharaan berhasil diperbarui.');
    }

    public function scheduleDestroy(Request $request, string $id)
    {
        $schedule = AssetMaintenanceSchedule::findOrFail($id);
        $this->authorizeMaintenanceAccess($schedule, $request);

        $schedule->delete();

        return redirect()->route('sarpras.pemeliharaan.schedule.index')
            ->with('success', 'Jadwal pemeliharaan berhasil dihapus.');
    }

    // ========================================================================
    // RIWAYAT PERAWATAN (LOG)
    // ========================================================================

    public function logIndex(Request $request)
    {
        $query = AssetMaintenanceLog::with(['asset', 'building', 'room', 'performer']);

        if (!$this->canViewAll($request)) {
            $query = $this->scopeToSchool($request, $query);
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

        $schedules = AssetMaintenanceSchedule::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->with(['asset', 'building', 'room'])
            ->orderBy('next_maintenance_date')->get();

        $assets = Asset::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('asset_name')->get();

        $buildings = AssetBuilding::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('building_name')->get();

        $rooms = AssetRoom::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')->get();

        $users = User::whereHas('roles')->orderBy('name')->get();

        return view('sarpras.pemeliharaan.log.create', compact('schedules', 'assets', 'buildings', 'rooms', 'users'));
    }

    public function logStore(Request $request)
    {
        $validated = $request->validate([
            'schedule_id'         => 'nullable|exists:asset_maintenance_schedules,id',
            'target_type'         => 'required|in:asset,building,room',
            'asset_id'            => 'required_if:target_type,asset|nullable|exists:assets,id',
            'building_id'         => 'required_if:target_type,building|nullable|exists:asset_buildings,id',
            'room_id'             => 'required_if:target_type,room|nullable|exists:asset_rooms,id',
            'maintenance_type'    => 'required|string|max:100',
            'maintenance_date'    => 'required|date',
            'performed_by'        => 'nullable|exists:users,id',
            'vendor_name'         => 'nullable|string|max:191',
            'actual_cost'         => 'nullable|numeric|min:0',
            'condition_before'     => 'nullable|in:' . implode(',', AssetMaintenanceLog::CONDITION_OPTIONS),
            'condition_after'     => 'nullable|in:' . implode(',', AssetMaintenanceLog::CONDITION_OPTIONS),
            'work_description'    => 'nullable|string',
            'parts_replaced'      => 'nullable|string',
            'next_action_needed' => 'nullable|string',
            'notes'               => 'nullable|string',
        ]);

        if (!empty($validated['asset_id'])) {
            $asset = Asset::find($validated['asset_id']);
            $validated['work_unit_id'] = $asset->work_unit_id;
            $validated['school_id'] = $asset->school_id;
        } elseif (!empty($validated['building_id'])) {
            $building = AssetBuilding::find($validated['building_id']);
            $validated['work_unit_id'] = $building->work_unit_id;
            $validated['school_id'] = $building->school_id;
        } elseif (!empty($validated['room_id'])) {
            $room = AssetRoom::find($validated['room_id']);
            $validated['work_unit_id'] = $room->work_unit_id;
            $validated['school_id'] = $room->school_id;
        }

        $validated['created_by'] = auth()->id();
        unset($validated['target_type']);

        AssetMaintenanceLog::create($validated);

        if (!empty($validated['schedule_id'])) {
            $schedule = AssetMaintenanceSchedule::find($validated['schedule_id']);
            if ($schedule) {
                $nextDate = $this->calculateNextMaintenanceDate($validated['maintenance_date'], $schedule->frequency);
                $schedule->update([
                    'last_maintenance_date' => $validated['maintenance_date'],
                    'next_maintenance_date' => $nextDate,
                ]);
            }
        }

        if (!empty($validated['asset_id']) && !empty($validated['condition_after'])) {
            Asset::find($validated['asset_id'])->update(['condition' => $validated['condition_after']]);
        }

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

        return match($frequency) {
            'harian'        => $date->addDay(),
            'mingguan'      => $date->addWeek(),
            'bulanan'       => $date->addMonth(),
            'triwulan'      => $date->addMonths(3),
            'semester'      => $date->addMonths(6),
            'tahunan'       => $date->addYear(),
            default         => $date->addMonth(),
        };
    }
}
