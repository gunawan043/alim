<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\AssetBuilding;
use App\Models\CleaningArea;
use App\Models\CleaningAssignment;
use App\Models\CleaningProgram;
use App\Models\CleaningReport;
use App\Models\CleaningReportItem;
use App\Models\CleaningTask;
use App\Models\GtkWorkUnit;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Sarpras\PhotoProofService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SarprasCleaningController extends SarprasBaseController
{
    public function __construct(protected PhotoProofService $photos) {}

    public function index(Request $request)
    {
        $canManage = $this->cleaningCanManage();
        $canReport = $canManage || $this->cleaningCanReport();
        abort_unless($canManage || $canReport, 403, 'Anda tidak memiliki akses ke modul kebersihan.');

        $user = auth()->user();
        $areaQuery = $this->cleaningAreaQuery($canManage, $user)
            ->when($request->filled('area_type'), fn ($q) => $q->where('area_type', $request->area_type));
        $areas = $areaQuery->get();

        $reportQuery = $this->cleaningReportQuery($request, $canManage, $user)
            ->when($request->filled('area_type'), fn ($q) => $q->whereHas('area', fn ($a) => $a->where('area_type', $request->area_type)));

        $stats = [
            'areas' => $areas->count(),
            'tasks' => $areas->sum('tasks_count'),
            'today' => (clone $reportQuery)->whereDate('report_date', today())->count(),
            'pending' => (clone $reportQuery)->where('status', 'submitted')->count(),
        ];

        $recentReports = (clone $reportQuery)->with(['area', 'officer', 'substituteFor', 'verifier'])
            ->orderByDesc('report_date')->limit(5)->get();
        $programCount = CleaningProgram::where('is_active', true)->count();

        // Dashboard kepatuhan: laporan bulan berjalan, verifikasi, dan program.
        $areaIds = $areas->pluck('id')->all();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthReports = CleaningReport::query()
            ->whereIn('area_id', $areaIds)
            ->whereBetween('report_date', [$monthStart, $monthEnd])
            ->get(['area_id', 'status', 'report_date']);

        $areasReportingToday = $monthReports->where('report_date', '>=', today())->pluck('area_id')->unique()->count();
        $verifiedMonth = $monthReports->where('status', 'verified')->count();
        $totalMonth = $monthReports->count();

        $programsDue = CleaningProgram::with(['checks' => fn ($q) => $q->where('year', now()->year)->where('month', now()->month)])
            ->where('is_active', true)
            ->when($areaIds !== [], fn ($q) => $q->where(fn ($sub) => $sub->whereIn('area_id', $areaIds)->orWhereNull('area_id')))
            ->get()
            ->filter(fn ($program) => in_array((string) now()->month, array_map('strval', $program->months ?? []), true));

        $programDone = $programsDue->filter(fn ($program) => $program->checks->contains(fn ($check) => (bool) $check->is_done))->count();

        $lowAreas = $areas->map(function ($area) use ($monthReports) {
            $last7 = $monthReports->where('area_id', $area->id)
                ->where('report_date', '>=', today()->subDays(6));
            $count = $last7->count();
            return [
                'area' => $area,
                'reports_7d' => $count,
                'percent' => (int) round(min(100, ($count / 7) * 100)),
            ];
        })->sortBy('percent')->take(5)->values();

        $trend = collect(range(6, 0))->map(function ($daysAgo) use ($monthReports) {
            $date = today()->subDays($daysAgo);
            return [
                'date' => $date,
                'reports' => $monthReports->filter(fn ($report) => $report->report_date?->isSameDay($date))->count(),
                'verified' => $monthReports->filter(fn ($report) => $report->report_date?->isSameDay($date) && $report->status === 'verified')->count(),
            ];
        });

        $compliance = [
            'today_percent' => $areas->count() ? (int) round(($areasReportingToday / $areas->count()) * 100) : 0,
            'areas_reporting_today' => $areasReportingToday,
            'verified_percent' => $totalMonth ? (int) round(($verifiedMonth / $totalMonth) * 100) : 0,
            'verified_month' => $verifiedMonth,
            'total_month' => $totalMonth,
            'program_percent' => $programsDue->count() ? (int) round(($programDone / $programsDue->count()) * 100) : 0,
            'program_done' => $programDone,
            'program_due' => $programsDue->count(),
            'low_areas' => $lowAreas,
            'trend' => $trend,
        ];

        return view('sarpras.kebersihan.index', compact('stats', 'recentReports', 'programCount', 'canManage', 'canReport', 'compliance'));
    }

    public function areas(Request $request)
    {
        $canManage = $this->cleaningCanManage();
        $canReport = $canManage || $this->cleaningCanReport();
        abort_unless($canManage || $canReport, 403, 'Anda tidak memiliki akses ke modul kebersihan.');

        $user = auth()->user();
        $areas = $this->cleaningAreaQuery($canManage, $user)
            ->when($request->filled('area_type'), fn ($q) => $q->where('area_type', $request->area_type))
            ->get();
        $users = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $buildings = AssetBuilding::query()->orderBy('building_name')->get(['id', 'building_name']);
        $rooms = \App\Models\AssetRoom::with('building')->where('is_active', true)->orderBy('room_name')
            ->get(['id', 'building_id', 'room_name', 'school_id', 'work_unit_id']);
        $officerIds = CleaningAssignment::query()->where('is_active', true)->pluck('user_id');
        $officers = User::query()->whereIn('id', $officerIds)->orderBy('name')->get(['id', 'name']);

        return view('sarpras.kebersihan.areas', compact('areas', 'users', 'officers', 'buildings', 'rooms', 'canManage', 'canReport'));
    }

    public function reports(Request $request)
    {
        $canManage = $this->cleaningCanManage();
        $canReport = $canManage || $this->cleaningCanReport();
        abort_unless($canManage || $canReport, 403, 'Anda tidak memiliki akses ke modul kebersihan.');

        $user = auth()->user();
        $areas = $this->cleaningAreaQuery($canManage, $user)->get();
        $reports = $this->cleaningReportQuery($request, $canManage, $user)
            ->when($request->filled('area_type'), fn ($q) => $q->whereHas('area', fn ($a) => $a->where('area_type', $request->area_type)))
            ->with(['area.building', 'officer', 'substituteFor', 'verifier'])
            ->orderByDesc('report_date')->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        return view('sarpras.kebersihan.reports', compact('reports', 'areas', 'canManage', 'canReport'));
    }

    public function programs(Request $request)
    {
        $canManage = $this->cleaningCanManage();
        abort_unless($canManage || $this->cleaningCanReport(), 403, 'Anda tidak memiliki akses ke modul kebersihan.');

        $year = (int) $request->input('year', now()->year);
        if ($year < 2000 || $year > 2100) $year = now()->year;

        $programs = CleaningProgram::with([
            'area', 'building', 'assignee',
            'checks' => fn ($q) => $q->where('year', $year),
        ])->where('is_active', true)
            ->when($request->filled('area_type'), fn ($q) => $q->whereHas('area', fn ($a) => $a->where('area_type', $request->area_type)))
            ->orderBy('title')->get();

        $users = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $areas = CleaningArea::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $buildings = AssetBuilding::query()->orderBy('building_name')->get(['id', 'building_name']);

        return view('sarpras.kebersihan.programs', compact('programs', 'year', 'users', 'areas', 'buildings', 'canManage'));
    }

    public function toggleProgramCheck(string $userId, Request $request, string $programId)
    {
        abort_unless($this->cleaningCanManage(), 403, 'Hanya koordinator yang dapat mencentang program.');

        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'is_done' => 'required|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $program = CleaningProgram::findOrFail($programId);
        $done = (bool) $validated['is_done'];

        \App\Models\CleaningProgramCheck::updateOrCreate(
            ['program_id' => $program->id, 'year' => $validated['year'], 'month' => $validated['month']],
            [
                'is_done' => $done,
                'checked_by' => $done ? auth()->id() : null,
                'checked_at' => $done ? now() : null,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return back()->with('success', $done ? 'Program ditandai terlaksana.' : 'Checklist program dibatalkan.');
    }

    private function cleaningAreaQuery(bool $canManage, $user)
    {
        $query = CleaningArea::with(['building', 'room', 'coordinator', 'activeAssignments.user', 'activeAssignments.backupUser'])
            ->withCount([
                'tasks as tasks_count' => fn ($q) => $q->where('is_active', true),
                'reports as today_reports_count' => fn ($q) => $q->whereDate('report_date', today()),
            ])
            ->where('is_active', true);

        if (! $canManage) {
            $areaIds = CleaningAssignment::query()->where('is_active', true)
                ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('backup_user_id', $user->id))
                ->pluck('area_id');
            $query->whereIn('id', $areaIds);
        }

        return $query->orderBy('name');
    }

    private function cleaningReportQuery(Request $request, bool $canManage, $user)
    {
        return CleaningReport::query()
            ->when(! $canManage, fn ($q) => $q->where('officer_id', $user->id))
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->area_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('report_date', $request->date));
    }

    public function showArea(string $userId, Request $request, string $areaId)
    {
        $area = CleaningArea::with([
            'building', 'room', 'coordinator',
            'tasks' => fn ($q) => $q->orderBy('sort_order'),
            'assignments.user', 'assignments.backupUser',
        ])->findOrFail($areaId);

        $this->authorizeCleaningArea($area);

        $canManage = $this->cleaningCanManage();
        $users = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $buildings = AssetBuilding::query()->orderBy('building_name')->get(['id', 'building_name']);
        $rooms = \App\Models\AssetRoom::with('building')
            ->where('is_active', true)
            ->orderBy('room_name')
            ->get(['id', 'building_id', 'room_name', 'school_id', 'work_unit_id']);
        $reports = $area->reports()->with(['officer', 'substituteFor', 'verifier'])->latest('report_date')->limit(20)->get();

        return view('sarpras.kebersihan.area', compact('area', 'users', 'buildings', 'rooms', 'reports', 'canManage'));
    }

    public function storeArea(Request $request)
    {
        abort_unless($this->cleaningCanManage(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'area_type' => 'required|in:'.implode(',', CleaningArea::TYPE_OPTIONS),
            'building_id' => 'nullable|uuid|exists:asset_buildings,id',
            'room_id' => 'nullable|uuid|exists:asset_rooms,id',
            'coordinator_id' => 'nullable|uuid|exists:users,id',
            'location_notes' => 'nullable|string|max:1000',
        ]);

        $building = $validated['building_id'] ? AssetBuilding::find($validated['building_id']) : null;
        $room = $validated['room_id'] ? \App\Models\AssetRoom::find($validated['room_id']) : null;

        CleaningArea::create([
            ...$validated,
            'work_unit_id' => $room?->work_unit_id ?? $building?->work_unit_id ?? $this->defaultUrtWorkUnitId(),
            'school_id' => $room?->school_id ?? $building?->school_id,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Area kerja kebersihan berhasil ditambahkan.');
    }

    public function updateArea(string $userId, Request $request, string $areaId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $area = CleaningArea::findOrFail($areaId);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'area_type' => 'required|in:'.implode(',', CleaningArea::TYPE_OPTIONS),
            'building_id' => 'nullable|uuid|exists:asset_buildings,id',
            'room_id' => 'nullable|uuid|exists:asset_rooms,id',
            'coordinator_id' => 'nullable|uuid|exists:users,id',
            'location_notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $building = $validated['building_id'] ? AssetBuilding::find($validated['building_id']) : null;
        $room = $validated['room_id'] ? \App\Models\AssetRoom::find($validated['room_id']) : null;

        $area->update([
            ...$validated,
            'work_unit_id' => $room?->work_unit_id ?? $building?->work_unit_id ?? $area->work_unit_id,
            'school_id' => $room?->school_id ?? $building?->school_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Petugas area berada di bawah koordinator area yang berlaku.
        CleaningAssignment::where('area_id', $area->id)
            ->update(['supervisor_id' => $validated['coordinator_id'] ?? null]);

        return back()->with('success', 'Area kerja diperbarui.');
    }

    public function toggleArea(string $userId, Request $request, string $areaId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $area = CleaningArea::findOrFail($areaId);
        $area->update(['is_active' => ! $area->is_active]);

        return back()->with('success', 'Status area kerja diperbarui.');
    }

    public function destroyArea(string $userId, Request $request, string $areaId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $area = CleaningArea::findOrFail($areaId);

        if ($area->reports()->exists()) {
            $area->update(['is_active' => false]);

            return back()->with('success', 'Area memiliki riwayat laporan, jadi dinonaktifkan agar histori tetap aman.');
        }

        DB::transaction(function () use ($area) {
            CleaningAssignment::where('area_id', $area->id)->delete();
            CleaningTask::where('area_id', $area->id)->delete();
            $area->delete();
        });

        return redirect()->route('sarpras.kebersihan.areas')
            ->with('success', 'Area kerja dihapus.');
    }

    public function destroyTask(string $userId, Request $request, string $taskId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $task = CleaningTask::findOrFail($taskId);

        if ($task->reportItems()->exists()) {
            $task->update(['is_active' => false]);

            return back()->with('success', 'Uraian sudah dipakai pada laporan, jadi dinonaktifkan agar histori tetap aman.');
        }

        $task->delete();

        return back()->with('success', 'Uraian pekerjaan dihapus.');
    }

    public function storeTask(string $userId, Request $request, string $areaId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $area = CleaningArea::findOrFail($areaId);

        $validated = $request->validate([
            'group_label' => 'nullable|string|max:100',
            'title' => 'required|string|max:191',
            'standard' => 'nullable|string|max:1000',
            'method' => 'nullable|string|max:60',
            'frequency' => 'required|in:'.implode(',', CleaningTask::FREQUENCY_OPTIONS),
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $area->tasks()->create([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? (($area->tasks()->max('sort_order') ?? 0) + 1),
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Uraian pekerjaan ditambahkan.');
    }

    public function updateTask(string $userId, Request $request, string $taskId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $task = CleaningTask::findOrFail($taskId);

        $validated = $request->validate([
            'group_label' => 'nullable|string|max:100',
            'title' => 'required|string|max:191',
            'standard' => 'nullable|string|max:1000',
            'method' => 'nullable|string|max:60',
            'frequency' => 'required|in:'.implode(',', CleaningTask::FREQUENCY_OPTIONS),
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $task->update($validated);

        return back()->with('success', 'Uraian pekerjaan diperbarui.');
    }

    public function toggleTask(string $userId, Request $request, string $taskId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $task = CleaningTask::findOrFail($taskId);
        $task->update(['is_active' => ! $task->is_active]);

        return back()->with('success', 'Status uraian pekerjaan diperbarui.');
    }

    public function storeAssignment(string $userId, Request $request, string $areaId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $area = CleaningArea::findOrFail($areaId);

        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'role' => 'required|in:petugas,koordinator',
            'backup_user_id' => 'nullable|uuid|exists:users,id|different:user_id',
            'supervisor_id' => 'nullable|uuid|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        CleaningAssignment::updateOrCreate(
            [
                'area_id' => $area->id,
                'user_id' => $validated['user_id'],
                'start_date' => $validated['start_date'] ?? null,
            ],
            [
                ...$validated,
                'supervisor_id' => $validated['supervisor_id'] ?? $area->coordinator_id,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Petugas area berhasil ditugaskan.');
    }

    public function toggleAssignment(string $userId, Request $request, string $assignmentId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $assignment = CleaningAssignment::findOrFail($assignmentId);
        $assignment->update(['is_active' => ! $assignment->is_active]);

        return back()->with('success', 'Status penugasan diperbarui.');
    }

    public function storeProgram(Request $request)
    {
        abort_unless($this->cleaningCanManage(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'area_id' => 'nullable|uuid|exists:cleaning_areas,id',
            'building_id' => 'nullable|uuid|exists:asset_buildings,id',
            'category' => 'required|in:'.implode(',', CleaningProgram::CATEGORY_OPTIONS),
            'frequency' => 'required|in:'.implode(',', CleaningTask::FREQUENCY_OPTIONS),
            'months' => 'nullable|array',
            'months.*' => 'integer|min:1|max:12',
            'standard' => 'nullable|string|max:1000',
            'method' => 'nullable|string|max:60',
            'assigned_to' => 'nullable|uuid|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        CleaningProgram::create([
            ...$validated,
            'months' => array_values(array_unique($validated['months'] ?? [])),
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Program kebersihan berkala berhasil ditambahkan.');
    }

    public function updateProgram(string $userId, Request $request, string $programId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $program = CleaningProgram::findOrFail($programId);

        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'area_id' => 'nullable|uuid|exists:cleaning_areas,id',
            'building_id' => 'nullable|uuid|exists:asset_buildings,id',
            'category' => 'required|in:'.implode(',', CleaningProgram::CATEGORY_OPTIONS),
            'frequency' => 'required|in:'.implode(',', CleaningTask::FREQUENCY_OPTIONS),
            'months' => 'nullable|array',
            'months.*' => 'integer|min:1|max:12',
            'standard' => 'nullable|string|max:1000',
            'method' => 'nullable|string|max:60',
            'assigned_to' => 'nullable|uuid|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $program->update([
            ...$validated,
            'months' => array_values(array_unique($validated['months'] ?? [])),
        ]);

        return back()->with('success', 'Program kebersihan diperbarui.');
    }

    public function destroyProgram(string $userId, Request $request, string $programId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $program = CleaningProgram::findOrFail($programId);
        $program->delete();

        return back()->with('success', 'Program kebersihan dihapus.');
    }

    public function toggleProgram(string $userId, Request $request, string $programId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $program = CleaningProgram::findOrFail($programId);
        $program->update(['is_active' => ! $program->is_active]);

        return back()->with('success', 'Status program kebersihan diperbarui.');
    }

    public function createReport(Request $request)
    {
        $canManage = $this->cleaningCanManage();
        abort_unless($canManage || $this->cleaningCanReport(), 403);

        $user = auth()->user();
        $substituteId = $request->query('substitute_for_user_id');
        $substitute = $substituteId && $substituteId !== $user->id
            ? User::where('is_active', true)->find($substituteId)
            : null;

        $areaQuery = CleaningArea::with(['tasks' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->where('is_active', true)
            ->when($request->filled('area_type'), fn ($q) => $q->where('area_type', $request->area_type));

        if ($substitute) {
            $areaIds = CleaningAssignment::query()
                ->where('is_active', true)
                ->where('user_id', $substitute->id)
                ->pluck('area_id');
            $areaQuery->whereIn('id', $areaIds);
        } elseif (! $canManage) {
            $areaIds = CleaningAssignment::query()
                ->where('is_active', true)
                ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('backup_user_id', $user->id))
                ->pluck('area_id');
            $areaQuery->whereIn('id', $areaIds);
        }

        $areas = $areaQuery->orderBy('name')->get();
        $selectedArea = null;

        if ($request->filled('area_id')) {
            $selectedArea = $areas->firstWhere('id', $request->area_id);
        } elseif ($areas->count() === 1) {
            $selectedArea = $areas->first();
        }

        $tasks = $selectedArea?->tasks ?? collect();
        $today = $request->input('report_date', today()->toDateString());

        $assignedIds = $selectedArea
            ? CleaningAssignment::where('area_id', $selectedArea->id)->where('is_active', true)->pluck('user_id')
            : collect();
        $officers = User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($assignedIds, $selectedArea) {
                $query->whereIn('id', $assignedIds)
                    ->orWhereHas('roles', fn ($role) => $role->whereIn('name', ['Petugas Kebersihan', 'Petugas Pertamanan']))
                    ->orWhereHas('gtkWorkUnits', function ($gtk) use ($selectedArea) {
                        $gtk->where(function ($jabatan) use ($selectedArea) {
                            if ($selectedArea?->area_type === 'taman') {
                                $jabatan->where('jabatan', 'like', '%PERTAMANAN%');
                            } else {
                                $jabatan->where('jabatan', 'like', '%KEBERSIHAN%')
                                    ->orWhere('jabatan', 'like', '%PERTAMANAN%');
                            }
                        });
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($officers->isEmpty() && auth()->check()) {
            $officers = collect([auth()->user()]);
        }

        // Daftar petugas yang bisa digantikan beserta area aktifnya.
        $substituteUserIds = CleaningAssignment::query()
            ->where('is_active', true)
            ->where('user_id', '!=', $user->id)
            ->distinct()
            ->pluck('user_id');
        $substituteOfficers = User::query()
            ->whereIn('id', $substituteUserIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $substituteAssignments = CleaningAssignment::with('area')
            ->where('is_active', true)
            ->whereIn('user_id', $substituteUserIds)
            ->get()
            ->groupBy('user_id');

        $report = $selectedArea
            ? CleaningReport::with('items')->where('area_id', $selectedArea->id)->whereDate('report_date', $today)->first()
            : null;
        $reportItems = $report ? $report->items->keyBy('task_id') : collect();
        $isLocked = $report && in_array($report->status, ['submitted', 'verified'], true);

        return view('sarpras.kebersihan.report-create', compact(
            'areas', 'selectedArea', 'tasks', 'officers', 'canManage', 'today',
            'report', 'reportItems', 'isLocked', 'substitute', 'substituteOfficers', 'substituteAssignments'
        ));
    }

    public function autosaveReport(Request $request)
    {
        $canManage = $this->cleaningCanManage();
        abort_unless($canManage || $this->cleaningCanReport(), 403);

        $validated = $request->validate([
            'area_id' => 'required|uuid|exists:cleaning_areas,id',
            'report_date' => 'required|date',
            'officer_id' => 'required|uuid|exists:users,id',
            'substitute_for_user_id' => 'nullable|uuid|exists:users,id|different:officer_id',
            'task_id' => 'required|uuid|exists:cleaning_tasks,id',
            'result' => 'required|in:'.implode(',', CleaningReportItem::RESULT_OPTIONS),
            'condition' => 'nullable|in:'.implode(',', CleaningReport::CONDITION_OPTIONS),
            'notes' => 'nullable|string|max:1000',
            'before_url' => 'nullable|url|max:2000',
            'after_url' => 'nullable|url|max:2000',
            'before_file' => 'nullable|image|max:8192',
            'after_file' => 'nullable|image|max:8192',
        ]);

        $area = CleaningArea::with('tasks')->findOrFail($validated['area_id']);
        $this->authorizeCleaningArea($area);

        if (! $canManage) {
            $validated['officer_id'] = auth()->id();
        }

        $report = CleaningReport::firstOrCreate(
            ['area_id' => $area->id, 'report_date' => $validated['report_date']],
            [
                'officer_id' => $validated['officer_id'],
                'substitute_for_user_id' => $validated['substitute_for_user_id'] ?? null,
                'overall_condition' => 'bersih',
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]
        );

        if ($report->status !== 'draft') {
            return response()->json(['message' => 'Laporan sudah dikirim dan tidak dapat diubah.'], 409);
        }

        $task = $area->tasks()->where('is_active', true)->findOrFail($validated['task_id']);
        $item = CleaningReportItem::firstOrNew([
            'report_id' => $report->id,
            'task_id' => $task->id,
        ]);

        if ($request->hasFile('before_file')) {
            $stored = $this->photos->store($request->file('before_file'), null, 'cleaning/'.$area->id.'/before');
            $item->before_photo_path = $stored['path'];
            $item->before_photo_url = $stored['url'];
        } elseif (! empty($validated['before_url'])) {
            $item->before_photo_url = $validated['before_url'];
            $item->before_photo_path = null;
        }

        if ($request->hasFile('after_file')) {
            $stored = $this->photos->store($request->file('after_file'), null, 'cleaning/'.$area->id.'/after');
            $item->after_photo_path = $stored['path'];
            $item->after_photo_url = $stored['url'];
        } elseif (! empty($validated['after_url'])) {
            $item->after_photo_url = $validated['after_url'];
            $item->after_photo_path = null;
        }

        $item->fill([
            'result' => $validated['result'],
            'condition' => $validated['condition'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);
        $item->save();

        $report->update([
            'officer_id' => $validated['officer_id'],
            'substitute_for_user_id' => $validated['substitute_for_user_id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'report_id' => $report->id,
            'item_id' => $item->id,
            'before_url' => $item->before_photo_url,
            'after_url' => $item->after_photo_url,
            'before_path' => $item->before_photo_path,
            'after_path' => $item->after_photo_path,
            'saved_at' => now()->format('H:i:s'),
        ]);
    }

    public function finalizeReport(Request $request)
    {
        $canManage = $this->cleaningCanManage();
        abort_unless($canManage || $this->cleaningCanReport(), 403);

        $validated = $request->validate([
            'area_id' => 'required|uuid|exists:cleaning_areas,id',
            'report_date' => 'required|date',
            'officer_id' => 'required|uuid|exists:users,id',
            'substitute_for_user_id' => 'nullable|uuid|exists:users,id|different:officer_id',
            'absence_reason' => 'nullable|string|max:1000',
        ]);

        $area = CleaningArea::with('tasks')->findOrFail($validated['area_id']);
        $this->authorizeCleaningArea($area);

        $report = CleaningReport::where('area_id', $area->id)->whereDate('report_date', $validated['report_date'])->first();
        if (! $report) {
            return response()->json(['message' => 'Belum ada item yang disimpan. Isi minimal satu pekerjaan terlebih dahulu.'], 422);
        }

        if (! $canManage) {
            $validated['officer_id'] = auth()->id();
        }

        $items = $report->items()->get()->keyBy('task_id');
        $missing = [];
        foreach ($area->tasks()->where('is_active', true)->get() as $task) {
            $item = $items->get($task->id);
            $hasBefore = $item && (! empty($item->before_photo_path) || ! empty($item->before_photo_url));
            $hasAfter = $item && (! empty($item->after_photo_path) || ! empty($item->after_photo_url));
            if (! $item || ! $hasBefore || ! $hasAfter) {
                $missing[] = $task->title;
            }
        }

        if ($missing !== []) {
            return response()->json([
                'message' => 'Masih ada pekerjaan yang belum lengkap: '.implode(', ', array_slice($missing, 0, 5)).(count($missing) > 5 ? '…' : ''),
                'missing' => $missing,
            ], 422);
        }

        $report->update([
            'officer_id' => $validated['officer_id'],
            'substitute_for_user_id' => $validated['substitute_for_user_id'] ?? null,
            'absence_reason' => $validated['absence_reason'] ?? null,
            'status' => 'submitted',
            'overall_condition' => 'bersih',
        ]);

        return response()->json([
            'success' => true,
            'redirect' => route('sarpras.kebersihan.report.show', ['reportId' => $report->id]),
        ]);
    }

    public function storeReport(Request $request)
    {
        $canManage = $this->cleaningCanManage();
        abort_unless($canManage || $this->cleaningCanReport(), 403);

        $validated = $request->validate([
            'area_id' => 'required|uuid|exists:cleaning_areas,id',
            'report_date' => 'required|date',
            'officer_id' => 'required|uuid|exists:users,id',
            'substitute_for_user_id' => 'nullable|uuid|exists:users,id|different:officer_id',
            'absence_reason' => 'nullable|string|max:1000',
            'overall_condition' => 'required|in:'.implode(',', CleaningReport::CONDITION_OPTIONS),
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array',
            'items.*.result' => 'required|in:'.implode(',', CleaningReportItem::RESULT_OPTIONS),
            'items.*.condition' => 'nullable|in:'.implode(',', CleaningReport::CONDITION_OPTIONS),
            'items.*.notes' => 'nullable|string|max:1000',
            'items.*.before_url' => 'nullable|url|max:2000',
            'items.*.after_url' => 'nullable|url|max:2000',
            'before_files.*' => 'nullable|image|max:8192',
            'after_files.*' => 'nullable|image|max:8192',
        ]);

        $area = CleaningArea::with(['tasks' => fn ($q) => $q->where('is_active', true)])->findOrFail($validated['area_id']);
        $this->authorizeCleaningArea($area);

        $exists = CleaningReport::query()
            ->where('area_id', $area->id)
            ->whereDate('report_date', $validated['report_date'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Laporan untuk area dan tanggal tersebut sudah ada.');
        }

        if (! $canManage) {
            $validated['officer_id'] = auth()->id();
        }

        $report = DB::transaction(function () use ($validated, $area, $request) {
            $report = CleaningReport::create([
                'area_id' => $area->id,
                'report_date' => $validated['report_date'],
                'officer_id' => $validated['officer_id'],
                'substitute_for_user_id' => $validated['substitute_for_user_id'] ?? null,
                'absence_reason' => $validated['absence_reason'] ?? null,
                'overall_condition' => $validated['overall_condition'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'submitted',
                'created_by' => auth()->id(),
            ]);

            foreach ($area->tasks as $task) {
                $input = $validated['items'][$task->id] ?? null;
                if (! $input) {
                    continue;
                }

                $hasBefore = ! empty($input['before_url']) || $request->hasFile("before_files.{$task->id}");
                $hasAfter = ! empty($input['after_url']) || $request->hasFile("after_files.{$task->id}");

                if (! $hasBefore || ! $hasAfter) {
                    throw ValidationException::withMessages([
                        "items.{$task->id}.before_url" => "Foto/link bukti sebelum dan sesudah wajib diisi untuk: {$task->title}",
                    ]);
                }

                $before = $this->photos->store(
                    $request->file("before_files.{$task->id}"),
                    $input['before_url'] ?? null,
                    'cleaning/'.$area->id.'/before'
                );
                $after = $this->photos->store(
                    $request->file("after_files.{$task->id}"),
                    $input['after_url'] ?? null,
                    'cleaning/'.$area->id.'/after'
                );

                CleaningReportItem::create([
                    'report_id' => $report->id,
                    'task_id' => $task->id,
                    'result' => $input['result'],
                    'condition' => $input['condition'] ?? null,
                    'notes' => $input['notes'] ?? null,
                    'before_photo_path' => $before['path'],
                    'before_photo_url' => $before['url'],
                    'after_photo_path' => $after['path'],
                    'after_photo_url' => $after['url'],
                ]);
            }

            return $report;
        });

        return redirect()->route('sarpras.kebersihan.report.show', ['reportId' => $report->id])
            ->with('success', 'Laporan kebersihan berhasil dikirim.');
    }

    public function showReport(string $userId, Request $request, string $reportId)
    {
        $report = CleaningReport::with([
            'area.building', 'area.room', 'officer', 'substituteFor', 'verifier', 'items.task',
        ])->findOrFail($reportId);

        abort_unless(
            $this->cleaningCanManage()
            || in_array(auth()->id(), [$report->officer_id, $report->substitute_for_user_id], true),
            403
        );

        $canVerify = $this->cleaningCanManage();

        return view('sarpras.kebersihan.report', compact('report', 'canVerify'));
    }

    public function verifyReport(string $userId, Request $request, string $reportId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $report = CleaningReport::findOrFail($reportId);

        $validated = $request->validate([
            'decision' => 'required|in:verified,rejected',
            'coordinator_note' => 'nullable|string|max:1500',
        ]);

        $report->update([
            'status' => $validated['decision'],
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'coordinator_note' => $validated['coordinator_note'] ?? null,
        ]);

        return back()->with('success', $validated['decision'] === 'verified'
            ? 'Laporan kebersihan diverifikasi.'
            : 'Laporan kebersihan dikembalikan untuk perbaikan.');
    }

    private function cleaningCanManage(): bool
    {
        if ($this->isUrtLeadership()) {
            return true;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $jabatan = GtkWorkUnit::query()->where('user_id', $user->id)->pluck('jabatan')->implode(' ');
        $jabatan = Str::upper($jabatan);

        return str_contains($jabatan, 'KOORDINATOR KEBERSIHAN')
            || str_contains($jabatan, 'KOORDINATOR SARANA PRASARANA')
            || $user->hasAnyRole(['Koordinator Kebersihan', 'Admin Sarpras', 'Unit Rumah Tangga']);
    }

    private function cleaningCanReport(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (CleaningAssignment::query()->where('is_active', true)
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('backup_user_id', $user->id))
            ->exists()) {
            return true;
        }

        $jabatan = Str::upper(GtkWorkUnit::query()->where('user_id', $user->id)->pluck('jabatan')->implode(' '));

        return str_contains($jabatan, 'KEBERSIHAN') || str_contains($jabatan, 'PERTAMANAN') || $user->hasRole('Petugas Kebersihan');
    }

    private function authorizeCleaningArea(CleaningArea $area): void
    {
        if ($this->cleaningCanManage()) {
            return;
        }

        $user = auth()->user();
        $assigned = CleaningAssignment::query()
            ->where('area_id', $area->id)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('backup_user_id', $user->id))
            ->exists();

        $substituteId = request('substitute_for_user_id');
        $substituteAssigned = $substituteId && CleaningAssignment::query()
            ->where('area_id', $area->id)
            ->where('is_active', true)
            ->where('user_id', $substituteId)
            ->exists();

        abort_unless($assigned || $substituteAssigned, 403, 'Area ini bukan tanggung jawab Anda atau petugas yang digantikan.');
    }

    private function defaultUrtWorkUnitId(): ?string
    {
        return WorkUnit::query()
            ->where('code', 'DEP-URT')
            ->orWhere('name', 'like', '%Unit Rumah Tangga%')
            ->value('id');
    }
}
