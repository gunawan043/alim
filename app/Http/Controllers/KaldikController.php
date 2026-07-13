<?php

namespace App\Http\Controllers;

use App\Models\Kaldik;
use App\Models\AcademicYear;
use App\Models\WorkUnit;
use App\Models\School;
use App\Models\NotificationUniversal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class KaldikController extends Controller
{
    /**
     * Ambil work_unit_id dari school context.
     */
    private function getUserWorkUnitId(Request $request): ?string
    {
        $schoolContextId = $request->attributes->get('schoolContextId');
        $school = $schoolContextId ? School::find($schoolContextId) : null;
        return $school?->work_unit_id;
    }

    /**
     * Cek apakah user adalah Super Admin atau Administrator (global view).
     */
    private function isGlobalUser(Request $request): bool
    {
        return $request->attributes->get('isGlobalView', false)
            || ($request->user() ? canPermission('kaldik-global') : false);
    }

    /**
     * Display a listing of Kaldik/Agenda (calendar view).
     * Super Admin/Administrator → lihat SEMUA kaldik + agenda (dari semua satuan kerja)
     * Admin TU → lihat Kaldik pondok (work_unit_id=null) + agenda milik satuan kerjanya sendiri
     */
    public function index(Request $request, string $userId)
    {
        $user = $request->user();
        $isGlobal = $this->isGlobalUser($request);
        $userWorkUnitId = $this->getUserWorkUnitId($request);

        $query = Kaldik::with(['academicYear', 'workUnit'])
            ->orderBy('start_date', 'asc');

        // ── FILTER KATEGORI ─────────────────────────────────
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // ── FILTER TAHUN AJARAN ──────────────────────────────
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        // ── FILTER AKTIF ────────────────────────────────────
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // ── FILTER WORK UNIT (NON-GLOBAL) ────────────────────
        if (!$isGlobal && $userWorkUnitId) {
            // Admin TU: hanya lihat agenda miliknya + semua kaldik pondok
            $query->where(function ($q) use ($userWorkUnitId) {
                $q->where('category', Kaldik::CATEGORY_KALDIK)
                  ->whereNull('work_unit_id');
            })->orWhere(function ($q) use ($userWorkUnitId) {
                $q->where('category', Kaldik::CATEGORY_AGENDA)
                  ->where('work_unit_id', $userWorkUnitId);
            });
        }

        $kaldiks = $query->get();

        // Convert to FullCalendar event format
        $kaldikEvents = $kaldiks->map(function ($item) {
            $isKaldik = $item->category === Kaldik::CATEGORY_KALDIK;
            return [
                'id' => $item->id,
                'title' => $item->name,
                'start' => $item->start_date->format('Y-m-d'),
                'end' => $item->end_date->format('Y-m-d'),
                'allDay' => true,
                'extendedProps' => [
                    'category' => $item->category,
                    'categoryLabel' => $isKaldik ? 'Kaldik' : 'Agenda Kegiatan',
                    'type' => $item->type,
                    'typeLabel' => $item->type ? (Kaldik::TYPE_OPTIONS[$item->type] ?? '-') : '-',
                    'color' => $item->color,
                    'work_unit_id' => $item->work_unit_id,
                    'work_unit_name' => $item->workUnit?->name ?? 'Pondok (Semua)',
                    'academic_year_id' => $item->academic_year_id,
                    'academic_year_name' => $item->academicYear?->name ?? '-',
                    'description' => $item->description ?? '',
                    'is_active' => $item->is_active,
                    'created_by_name' => $item->creator?->name ?? null,
                ],
            ];
        });

        // Authorization flags for JS
        $canCreate = Gate::allows('create', Kaldik::class);
        $canUpdate = Gate::allows('update', new Kaldik());

        // Dropdowns
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $workUnits = $isGlobal ? WorkUnit::active()->orderBy('name')->get() : collect();

        // Label role untuk JS
        $isAdminTU = !$isGlobal && canPermission('kaldik-admin-tu');

        return view('kaldik.index', compact(
            'kaldikEvents',
            'academicYears',
            'workUnits',
            'userId',
            'canCreate',
            'canUpdate',
            'isAdminTU',
            'isGlobal',
            'userWorkUnitId',
        ));
    }

    /**
     * Show the form for creating a new Kaldik/Agenda.
     */
    public function create(Request $request, string $userId)
    {
        $isGlobal = $this->isGlobalUser($request);
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $workUnits = $isGlobal ? WorkUnit::active()->orderBy('name')->get() : collect();

        return view('kaldik.create', compact('academicYears', 'workUnits', 'userId'));
    }

    /**
     * Store a newly created Kaldik/Agenda.
     *
     * - Super Admin / Administrator → boleh pilih kategori & satuan kerja
     * - Admin Tata Usaha           → otomatis category=agenda, work_unit_id=milik sendiri
     */
    public function store(Request $request, string $userId)
    {
        $user = $request->user();
        $isGlobal = $this->isGlobalUser($request);
        $isAdminTU = !$isGlobal && canPermission('kaldik-admin-tu');

        $rules = [
            'name' => 'required|string|max:255',
            'category' => $isAdminTU ? 'nullable' : 'required|in:kaldik,agenda',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'type' => 'nullable|in:tahunan,mid_semester,lainnya',
            'color' => 'nullable|string|max:30',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ];

        if ($isAdminTU) {
            $rules['work_unit_id'] = 'nullable'; // tidak perlu validasi, auto-set
        } else {
            $rules['work_unit_id'] = 'nullable|exists:work_units,id';
        }

        $validated = $request->validate($rules);

        // Admin TU: force category = agenda, auto-assign work_unit_id
        if ($isAdminTU) {
            $validated['category'] = Kaldik::CATEGORY_AGENDA;
            $validated['work_unit_id'] = $this->getUserWorkUnitId($request);
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['created_by'] = $user->id;

        $kaldik = Kaldik::create($validated);

        // ── KIRIM NOTIFIKASI ────────────────────────────────
        $this->sendNotification($kaldik, $user);

        return response()->json([
            'success' => true,
            'message' => 'Agenda kegiatan berhasil disimpan.',
        ]);
    }

    /**
     * Display the specified Kaldik/Agenda.
     */
    public function show(Request $request, string $userId, string $kaldikId)
    {
        $kaldik = Kaldik::with(['academicYear', 'workUnit', 'creator'])->findOrFail($kaldikId);
        return view('kaldik.show', compact('kaldik', 'userId'));
    }

    /**
     * Show the form for editing.
     */
    public function edit(Request $request, string $userId, string $kaldikId)
    {
        $kaldik = Kaldik::findOrFail($kaldikId);
        $isGlobal = $this->isGlobalUser($request);
        $user = $request->user();

        // Admin TU → hanya boleh edit agenda miliknya sendiri
        if (!$isGlobal && canPermission('kaldik-admin-tu')) {
            if ($kaldik->category !== Kaldik::CATEGORY_AGENDA) {
                abort(403, 'Admin TU hanya bisa mengedit agenda kegiatan.');
            }
            $schoolContextId = $request->attributes->get('schoolContextId');
            $school = School::find($schoolContextId);
            if ($kaldik->work_unit_id !== $school?->work_unit_id) {
                abort(403, 'Anda tidak memiliki akses untuk mengedit agenda ini.');
            }
        }

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $workUnits = $isGlobal ? WorkUnit::active()->orderBy('name')->get() : collect();

        return view('kaldik.edit', compact('kaldik', 'academicYears', 'workUnits', 'userId', 'isGlobal'));
    }

    /**
     * Update the specified Kaldik/Agenda.
     */
    public function update(Request $request, string $userId, string $kaldikId)
    {
        $kaldik = Kaldik::findOrFail($kaldikId);
        $isGlobal = $this->isGlobalUser($request);
        $user = $request->user();

        // Admin TU → hanya boleh update agenda miliknya sendiri
        if (!$isGlobal && canPermission('kaldik-admin-tu')) {
            if ($kaldik->category !== Kaldik::CATEGORY_AGENDA) {
                abort(403, 'Admin TU hanya bisa mengedit agenda kegiatan.');
            }
            $schoolContextId = $request->attributes->get('schoolContextId');
            $school = School::find($schoolContextId);
            if ($kaldik->work_unit_id !== $school?->work_unit_id) {
                abort(403, 'Anda tidak memiliki akses untuk mengedit agenda ini.');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:kaldik,agenda',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'type' => 'nullable|in:tahunan,mid_semester,lainnya',
            'color' => 'nullable|string|max:30',
            'work_unit_id' => 'nullable|exists:work_units,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $kaldik->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Berhasil diperbarui.']);
        }

        return redirect()->route('user.kaldik.index', ['userId' => $userId])
            ->with('success', 'Berhasil diperbarui.');
    }

    /**
     * Remove the specified Kaldik/Agenda.
     */
    public function destroy(Request $request, string $userId, string $kaldikId)
    {
        $kaldik = Kaldik::findOrFail($kaldikId);
        $isGlobal = $this->isGlobalUser($request);
        $user = $request->user();

        // Admin TU → hanya boleh hapus agenda miliknya sendiri
        if (!$isGlobal && canPermission('kaldik-admin-tu')) {
            if ($kaldik->category !== Kaldik::CATEGORY_AGENDA) {
                abort(403, 'Admin TU hanya bisa menghapus agenda kegiatan.');
            }
            $schoolContextId = $request->attributes->get('schoolContextId');
            $school = School::find($schoolContextId);
            if ($kaldik->work_unit_id !== $school?->work_unit_id) {
                abort(403, 'Anda tidak memiliki akses untuk menghapus agenda ini.');
            }
        } elseif (Gate::denies('update', $kaldik)) {
            abort(403);
        }

        $kaldik->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Berhasil dihapus.']);
        }

        return redirect()->route('user.kaldik.index', ['userId' => $userId])
            ->with('success', 'Berhasil dihapus.');
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(Request $request, string $userId, string $kaldikId)
    {
        $kaldik = Kaldik::findOrFail($kaldikId);

        if (Gate::denies('update', $kaldik)) {
            abort(403);
        }

        $kaldik->update(['is_active' => !$kaldik->is_active]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Status diperbarui.']);
        }

        return back()->with('success', 'Status diperbarui.');
    }

    /**
     * Kirim notifikasi ke semua user terkait saat agenda dibuat.
     */
    private function sendNotification(Kaldik $kaldik, $creator): void
    {
        $isAgenda = $kaldik->category === Kaldik::CATEGORY_AGENDA;
        $title = $isAgenda
            ? "📅 Agenda Kegiatan Baru"
            : "📅 Kaldik Baru";

        $workUnitName = $kaldik->workUnit?->name ?? 'Pondok';
        $message = $isAgenda
            ? "{$creator->name} menambahkan agenda kegiatan \"{$kaldik->name}\" di {$workUnitName} ({$kaldik->start_date->format('d M Y')} – {$kaldik->end_date->format('d M Y')})"
            : "{$creator->name} menambahkan kaldik \"{$kaldik->name}\" ({$kaldik->start_date->format('d M Y')} – {$kaldik->end_date->format('d M Y')})";

        // Target berdasarkan scope:
        // - Kaldik pondok (work_unit_id=null) → kirim ke semua Admin TU
        // - Agenda satuan kerja → kirim ke Admin TU satuan kerja + Super Admin/Administrator
        $targetUserIds = [];

        if ($isAgenda && $kaldik->work_unit_id) {
            // Agenda satuan kerja → kirim ke Admin TU di satuan kerja itu
            $adminTUIds = \App\Models\GtkWorkUnit::where('work_unit_id', $kaldik->work_unit_id)
                ->whereHas('user', fn($q) => $q->whereIn('id', usersHavingPermission('admin.tu.assessable')))
                ->pluck('user_id')
                ->toArray();
            $targetUserIds = array_merge($targetUserIds, $adminTUIds);
        }

        // Selalu kirim ke Super Admin & Administrator
        $adminIds = usersHavingPermission('general_admin.administrable');
        $targetUserIds = array_unique(array_merge($targetUserIds, $adminIds));

        // Hapus creator dari list agar tidak notifikasi ke dirinya sendiri
        $targetUserIds = array_filter($targetUserIds, fn($id) => $id !== $creator->id);

        foreach ($targetUserIds as $targetUserId) {
            NotificationUniversal::create([
                'id' => (string) Str::uuid(),
                'user_id' => $targetUserId,
                'module' => 'kaldik',
                'reference_type' => Kaldik::class,
                'reference_id' => $kaldik->id,
                'type' => $isAgenda ? 'agenda_created' : 'kaldik_created',
                'action' => 'created',
                'title' => $title,
                'message' => $message,
                'priority' => 'medium',
                'action_url' => "/{$targetUserId}/kaldik",
                'action_text' => 'Lihat Agenda',
                'is_read' => false,
            ]);
        }
    }
}