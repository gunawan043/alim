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
        $areaQuery = $this->cleaningAreaQuery($canManage, $user);
        $areas = $areaQuery->get();

        $reportQuery = $this->cleaningReportQuery($request, $canManage, $user);

        $stats = [
            'areas' => $areas->count(),
            'tasks' => $areas->sum('tasks_count'),
            'today' => (clone $reportQuery)->whereDate('report_date', today())->count(),
            'pending' => (clone $reportQuery)->where('status', 'submitted')->count(),
        ];

        $recentReports = (clone $reportQuery)->with(['area', 'officer', 'substituteFor', 'verifier'])
            ->orderByDesc('report_date')->limit(5)->get();
        $programCount = CleaningProgram::where('is_active', true)->count();

        return view('sarpras.kebersihan.index', compact('stats', 'recentReports', 'programCount', 'canManage', 'canReport'));
    }

    public function areas(Request $request)
    {
        $canManage = $this->cleaningCanManage();
        $canReport = $canManage || $this->cleaningCanReport();
        abort_unless($canManage || $canReport, 403, 'Anda tidak memiliki akses ke modul kebersihan.');

        $user = auth()->user();
        $areas = $this->cleaningAreaQuery($canManage, $user)->get();
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
        ])->where('is_active', true)->orderBy('title')->get();

        $users = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $areas = CleaningArea::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $buildings = AssetBuilding::query()->orderBy('building_name')->get(['id', 'building_name']);

        return view('sarpras.kebersihan.programs', compact('programs', 'year', 'users', 'areas', 'buildings', 'canManage'));
    }

    public function toggleProgramCheck(Request $request, string $programId)
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

    public function showArea(Request $request, string $areaId)
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

    public function updateArea(Request $request, string $areaId)
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

        return back()->with('success', 'Area kerja diperbarui.');
    }

    public function toggleArea(Request $request, string $areaId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $area = CleaningArea::findOrFail($areaId);
        $area->update(['is_active' => ! $area->is_active]);

        return back()->with('success', 'Status area kerja diperbarui.');
    }

    public function storeTask(Request $request, string $areaId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $area = CleaningArea::findOrFail($areaId);

        $validated = $request->validate([
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

    public function updateTask(Request $request, string $taskId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $task = CleaningTask::findOrFail($taskId);

        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'standard' => 'nullable|string|max:1000',
            'method' => 'nullable|string|max:60',
            'frequency' => 'required|in:'.implode(',', CleaningTask::FREQUENCY_OPTIONS),
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $task->update($validated);

        return back()->with('success', 'Uraian pekerjaan diperbarui.');
    }

    public function toggleTask(Request $request, string $taskId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $task = CleaningTask::findOrFail($taskId);
        $task->update(['is_active' => ! $task->is_active]);

        return back()->with('success', 'Status uraian pekerjaan diperbarui.');
    }

    public function storeAssignment(Request $request, string $areaId)
    {
        abort_unless($this->cleaningCanManage(), 403);
        $area = CleaningArea::findOrFail($areaId);

        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'role' => 'required|in:petugas,koordinator',
            'backup_user_id' => 'nullable|uuid|exists:users,id|different:user_id',
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
                'is_active' => true,
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Petugas area berhasil ditugaskan.');
    }

    public function toggleAssignment(Request $request, string $assignmentId)
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

    public function toggleProgram(Request $request, string $programId)
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
        $areaQuery = CleaningArea::with(['tasks' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->where('is_active', true);

        if (! $canManage) {
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
        $users = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $today = $request->input('report_date', today()->toDateString());

        return view('sarpras.kebersihan.report-create', compact('areas', 'selectedArea', 'tasks', 'users', 'canManage', 'today'));
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
            'score' => 'nullable|integer|min:0|max:100',
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
                'score' => $validated['score'] ?? null,
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

        return redirect()->route('sarpras.kebersihan.report.show', $report->id)
            ->with('success', 'Laporan kebersihan berhasil dikirim.');
    }

    public function showReport(Request $request, string $reportId)
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

    public function verifyReport(Request $request, string $reportId)
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

        return str_contains($jabatan, 'KEBERSIHAN') || $user->hasRole('Petugas Kebersihan');
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

        abort_unless($assigned, 403, 'Area ini bukan tanggung jawab Anda.');
    }

    private function defaultUrtWorkUnitId(): ?string
    {
        return WorkUnit::query()
            ->where('code', 'DEP-URT')
            ->orWhere('name', 'like', '%Unit Rumah Tangga%')
            ->value('id');
    }
}
