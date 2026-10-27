<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\GtkWorkUnit;
use App\Models\Kaldik;
use App\Models\NotificationUniversal;
use App\Models\School;
use App\Models\WorkUnit;
use App\Policies\KaldikPolicy;
use App\Support\UserJabatan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KaldikController extends Controller
{
    /**
     * Policy Kaldik dipanggil langsung (bukan via Gate) karena registrar
     * snapshot global men-intercept ability Gate bernama 'create'/'update'.
     */
    private function policy(): KaldikPolicy
    {
        return app(KaldikPolicy::class);
    }

    /**
     * Ambil work_unit_id dari school context.
     */
    private function getUserWorkUnitId(Request $request): ?string
    {
        $schoolContextId = $request->attributes->get('schoolContextId');
        $school = $schoolContextId ? School::find($schoolContextId) : null;
        $user = $request->user();

        return $school?->work_unit_id
            ?? $user?->primaryWorkUnit?->work_unit_id
            ?? $user?->employment?->school?->work_unit_id
            ?? $user?->gtkWorkUnits()?->where('is_primary', true)->value('work_unit_id');
    }

    /**
     * Cek apakah user adalah Super Admin atau Administrator (global view).
     */
    private function canManageAgenda(Request $request, Kaldik $kaldik): bool
    {
        $user = $request->user();

        if ($this->isGlobalUser($request)) {
            return true;
        }

        if ($kaldik->category === Kaldik::CATEGORY_KALDIK) {
            return $this->canCreateKaldik($request) && $this->policy()->update($user, $kaldik);
        }

        if ($kaldik->visibility === Kaldik::VISIBILITY_PRIBADI) {
            return (string) $kaldik->owner_user_id === (string) $user?->id;
        }

        if (canPermission('kaldik-admin-tu') && (string) $kaldik->work_unit_id === (string) $this->getUserWorkUnitId($request)) {
            return true;
        }

        // Agenda satuan: hanya pembuat atau Admin TU pada unit yang sama.
        return (string) $kaldik->created_by === (string) $user?->id;
    }

    private function isPersonalia($user): bool
    {
        $roles = method_exists($user, 'effectiveRoles') ? $user->effectiveRoles() : [];

        return in_array('personalia', $roles, true)
            || UserJabatan::hasAny($user, ['personalia', 'kepala personalia', 'admin personalia', 'staf personalia']);
    }

    private function canCreateKaldik(Request $request): bool
    {
        return $this->isGlobalUser($request)
            || $this->policy()->create($request->user())
            || $this->isPersonalia($request->user());
    }

    private function canCreateUnitAgenda(Request $request): bool
    {
        $user = $request->user();

        return $this->getUserWorkUnitId($request) && (
            $this->canCreateKaldik($request)
            || canPermission('kaldik-admin-tu')
            || UserJabatan::hasAny($user, [
                'kepala satuan pendidikan', 'wakil kepala satuan pendidikan',
                'kepala tata usaha', 'tata usaha', 'staf tata usaha',
                'kepala asrama', 'wakil kepala asrama', 'wakil asrama',
                'koordinator kurikulum',
            ])
        );
    }

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

        // ── FILTER SEMESTER ──────────────────────────────────
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        // ── FILTER AKTIF ────────────────────────────────────
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // ── SCOPE AKSES (NON-GLOBAL) ─────────────────────────
        // 1) Kaldik pondok (global) — untuk semua GTK.
        // 2) Agenda SATUAN — hanya GTK dalam satuan kerja tersebut.
        // 3) Agenda PRIBADI — HANYA pemiliknya (tidak bocor ke GTK lain).
        if (! $isGlobal) {
            $query->where(function ($q) use ($userWorkUnitId, $user) {
                $q->where(function ($q2) {
                    $q2->where('category', Kaldik::CATEGORY_KALDIK)
                        ->whereNull('work_unit_id');
                });

                if ($userWorkUnitId) {
                    $q->orWhere(function ($q2) use ($userWorkUnitId) {
                        $q2->where('category', Kaldik::CATEGORY_AGENDA)
                            ->where('visibility', '!=', Kaldik::VISIBILITY_PRIBADI)
                            ->where('work_unit_id', $userWorkUnitId);
                    });
                }

                $q->orWhere(function ($q2) use ($user) {
                    $q2->where('category', Kaldik::CATEGORY_AGENDA)
                        ->where('visibility', Kaldik::VISIBILITY_PRIBADI)
                        ->where('owner_user_id', $user->id);
                });
            });
        }

        $kaldiks = $query->get();

        // Peta tanggal → event: dipakai oleh tampilan KALENDER TAHUNAN (12 bulan).
        $eventsByDate = [];
        foreach ($kaldiks as $item) {
            if (! $item->start_date || ! $item->end_date) {
                continue;
            }

            $cursor = $item->start_date->copy();
            $guard = 0;
            while ($cursor->lte($item->end_date) && $guard < 400) {
                $eventsByDate[$cursor->toDateString()][] = [
                    'title' => $item->name,
                    'color' => $item->color,
                    'category' => $item->category,
                ];
                $cursor->addDay();
                $guard++;
            }
        }

        // Convert to FullCalendar event format
        $kaldikEvents = $kaldiks->map(function ($item) use ($request) {
            $isKaldik = $item->category === Kaldik::CATEGORY_KALDIK;

            return [
                'id' => $item->id,
                'title' => $item->name,
                'start' => $item->start_date->format('Y-m-d'),
                'end' => $item->end_date->format('Y-m-d'),
                'allDay' => true,
                'extendedProps' => [
                    'category' => $item->category,
                    'categoryLabel' => $isKaldik ? 'Kaldik Pondok' : (($item->visibility ?? '') === 'pribadi' ? 'Agenda Pribadi' : 'Agenda Satuan Kerja'),
                    'visibility' => $item->visibility,
                    'type' => $item->type,
                    'typeLabel' => $item->type ? (Kaldik::TYPE_OPTIONS[$item->type] ?? '-') : '-',
                    'semester' => $item->semester,
                    'semesterLabel' => $item->semester ? (Kaldik::SEMESTER_OPTIONS[$item->semester] ?? '-') : '-',
                    'color' => $item->color,
                    'work_unit_id' => $item->work_unit_id,
                    'work_unit_name' => $item->workUnit?->name ?? 'Pondok (Semua)',
                    'academic_year_id' => $item->academic_year_id,
                    'academic_year_name' => $item->academicYear?->name ?? '-',
                    'description' => $item->description ?? '',
                    'is_active' => $item->is_active,
                    'created_by_name' => $item->creator?->name ?? null,
                    'canEdit' => $this->canManageAgenda($request, $item),
                    'categoryChoice' => $isKaldik ? 'kaldik' : (($item->visibility ?? '') === 'pribadi' ? 'agenda_pribadi' : 'agenda_satuan'),
                ],
            ];
        });

        // Label role untuk JS
        $isAdminTU = ! $isGlobal && canPermission('kaldik-admin-tu');

        // Semua GTK boleh membuat agenda; edit dihitung per agenda.
        $canCreate = true;
        $canCreateKaldik = $this->canCreateKaldik($request);
        $canCreateUnit = $this->canCreateUnitAgenda($request);
        $canUpdate = $this->policy()->update($user, new Kaldik);
        $kaldikCategoryOptions = [];
        if ($canCreateKaldik) { $kaldikCategoryOptions['kaldik'] = 'Kaldik Pondok'; }
        if ($canCreateUnit) { $kaldikCategoryOptions['agenda_satuan'] = 'Agenda Satuan Kerja'; }
        $kaldikCategoryOptions['agenda_pribadi'] = 'Agenda Kegiatan (Pribadi)';

        // Dropdowns
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $workUnits = $isGlobal ? WorkUnit::active()->orderBy('name')->get() : collect();

        // Bulan-bulan untuk tampilan KALENDER TAHUNAN (mengikuti TA terpilih; fallback TA aktif).
        $annualAy = $request->filled('academic_year_id')
            ? $academicYears->firstWhere('id', $request->input('academic_year_id'))
            : (\App\Models\AcademicYear::where('is_active', true)->first() ?? $academicYears->first());

        $annualStart = $annualAy?->start_date
            ? $annualAy->start_date->copy()->startOfMonth()
            : now()->startOfYear();

        $annualMonths = collect(range(0, 11))->map(function ($i) use ($annualStart) {
            $m = $annualStart->copy()->addMonths($i);

            return [
                'ym' => $m->format('Y-m'),
                'label' => $m->translatedFormat('F Y'),
                'start' => $m->copy()->startOfMonth(),
                'end' => $m->copy()->endOfMonth(),
            ];
        });
        $annualLabel = $annualAy?->name;

        return view('kaldik.index', compact('eventsByDate', 'annualMonths', 'annualLabel', 'kaldiks') + compact(
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
        $canKaldik = $this->canCreateKaldik($request);
        $canUnit = $this->canCreateUnitAgenda($request);
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $workUnits = $canKaldik ? WorkUnit::active()->orderBy('name')->get() : collect();
        $userWorkUnitId = $this->getUserWorkUnitId($request);

        $categoryOptions = [];
        if ($canKaldik) {
            $categoryOptions['kaldik'] = 'Kaldik Pondok';
        }
        if ($canUnit) {
            $categoryOptions['agenda_satuan'] = 'Agenda Satuan Kerja';
        }
        $categoryOptions['agenda_pribadi'] = 'Agenda Kegiatan (Pribadi)';

        $forcedCategory = count($categoryOptions) === 1 ? array_key_first($categoryOptions) : null;
        $kaldikCategoryOptions = $categoryOptions;

        return view('kaldik.create', compact(
            'academicYears', 'workUnits', 'userId', 'categoryOptions', 'forcedCategory', 'userWorkUnitId', 'kaldikCategoryOptions',
        ));
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
        $canKaldik = $this->canCreateKaldik($request);
        $canUnit = $this->canCreateUnitAgenda($request);

        $rules = [
            'name' => 'required|string|max:255',
            'category' => $canKaldik
                ? 'required|in:kaldik,agenda_satuan,agenda_pribadi,agenda'
                : 'required|in:agenda_satuan,agenda_pribadi,agenda',
            'semester' => 'nullable|in:ganjil,genap',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'type' => 'nullable|in:'.implode(',', array_keys(Kaldik::TYPE_OPTIONS)),
            'color' => 'nullable|string|max:30',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
            'visibility' => 'nullable|in:satuan,pribadi',
        ];

        $rules['work_unit_id'] = $canKaldik ? 'nullable|exists:work_units,id' : 'nullable';

        $validated = $request->validate($rules);

        $choice = $validated['category'] ?? 'agenda_pribadi';

        if ($choice === 'kaldik') {
            if (! $canKaldik) {
                abort(403, 'Anda tidak berhak membuat Kaldik pondok.');
            }
            $validated['category'] = Kaldik::CATEGORY_KALDIK;
            $validated['visibility'] = Kaldik::VISIBILITY_SATUAN;
        } elseif ($choice === 'agenda_satuan' || ($choice === 'agenda' && ($validated['visibility'] ?? 'satuan') === 'satuan')) {
            if (! $canUnit) {
                abort(403, 'Anda tidak berhak membuat agenda satuan kerja.');
            }
            $validated['category'] = Kaldik::CATEGORY_AGENDA;
            $validated['visibility'] = Kaldik::VISIBILITY_SATUAN;
            // Super Admin dapat membuat agenda untuk satuan kerja lain.
            $validated['work_unit_id'] = ($canKaldik && ! empty($validated['work_unit_id']))
                ? $validated['work_unit_id']
                : $this->getUserWorkUnitId($request);
        } else {
            $validated['category'] = Kaldik::CATEGORY_AGENDA;
            $validated['visibility'] = Kaldik::VISIBILITY_PRIBADI;
            $validated['work_unit_id'] = $this->getUserWorkUnitId($request);
        }

        $validated['work_unit_id'] = $validated['work_unit_id'] ?? null;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['created_by'] = $user->id;

        // Privasi: agenda pribadi disimpan dengan pemiliknya; agenda satuan tanpa pemilik.
        $validated['visibility'] = ($validated['visibility'] ?? Kaldik::VISIBILITY_SATUAN) === Kaldik::VISIBILITY_PRIBADI
            ? Kaldik::VISIBILITY_PRIBADI
            : Kaldik::VISIBILITY_SATUAN;
        $validated['owner_user_id'] = $validated['visibility'] === Kaldik::VISIBILITY_PRIBADI ? $user->id : null;

        if ($validated['category'] === Kaldik::CATEGORY_AGENDA
            && $validated['visibility'] === Kaldik::VISIBILITY_SATUAN
            && ! $validated['work_unit_id']) {
            return back()->withInput()->withErrors([
                'category' => 'Satuan kerja Anda belum terdeteksi. Pilih Agenda Kegiatan (Pribadi) atau hubungi admin.',
            ]);
        }

        // Legenda default: libur → merah, dst. (dapat ditimpa settings).
        $validated['color'] = $validated['color'] ?? kaldik_default_color($validated['type'] ?? null);

        $kaldik = Kaldik::create($validated);

        // Notifikasi IN-APP GTK: per satuan kerja, fallback GLOBAL bila
        // tidak ada satuan kerja (mis. dibuat oleh Super Admin).
        try {
            // Kaldik PONDOK (kategori kaldik) = global: semua GTK seluruh satuan
            // kerja termasuk Super Admin. Agenda = satuan kerja terkait.
            $targetWorkUnit = $kaldik->work_unit_id;

            if ($kaldik->category === Kaldik::CATEGORY_AGENDA && ! $targetWorkUnit) {
                $targetWorkUnit = $user->gtkWorkUnits()?->where('is_primary', true)->value('work_unit_id')
                    ?? $user->gtkWorkUnits()?->value('work_unit_id');
            }

            $payload = [
                'module' => 'kaldik',
                'reference_type' => 'kaldik',
                'reference_id' => $kaldik->id,
                'type' => 'info',
                'action' => 'kaldik_created',
                'title' => 'Kaldik: '.$kaldik->name,
                'message' => 'Agenda '.($kaldik->type ?? 'kegiatan').' tanggal '
                    .($kaldik->start_date?->format('d/m/Y') ?? '-').' telah ditambahkan ke kalender pendidikan.',
                'action_url' => fn (string $recipientId) => route('user.kaldik.index', ['userId' => $recipientId]),
                'action_text' => 'Lihat Kaldik',
                'priority' => 'medium',
            ];

            app(\App\Services\NotificationUniversalService::class)->notifyGtk($targetWorkUnit, $payload);

            // Pembuat selalu menerima (mis. Super Admin membuat agenda satuan lain).
            $alreadyNotified = \App\Models\NotificationUniversal::query()
                ->where('user_id', $user->id)
                ->where('reference_id', $kaldik->id)
                ->exists();

            if (! $alreadyNotified) {
                app(\App\Services\NotificationUniversalService::class)->send($user->id, $payload);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Notif Kaldik gagal: '.$e->getMessage());
        }

        // Notifikasi WA untuk libur (mode terpusat nomor admin ALIM).
        if (($validated['type'] ?? null) === Kaldik::TYPE_LIBUR) {
            try {
                app(\App\Services\Notifications\GuardianWhatsAppNotifier::class)->notifyHoliday($kaldik);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('WA notif libur gagal: '.$e->getMessage());
            }
        }

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
        $user = $request->user();
        $isGlobal = $this->isGlobalUser($request);

        if (! $isGlobal && $kaldik->category === Kaldik::CATEGORY_AGENDA) {
            $allowed = $kaldik->visibility === Kaldik::VISIBILITY_PRIBADI
                ? (string) $kaldik->owner_user_id === (string) $user?->id
                : ((string) $kaldik->work_unit_id === (string) $this->getUserWorkUnitId($request));

            abort_unless($allowed, 403, 'Agenda ini bukan milik satuan kerja Anda.');
        }

        return view('kaldik.show', compact('kaldik', 'userId'));
    }

    /**
     * Show the form for editing.
     */
    public function edit(Request $request, string $userId, string $kaldikId)
    {
        $kaldik = Kaldik::findOrFail($kaldikId);
        $isGlobal = $this->isGlobalUser($request);

        abort_unless($this->canManageAgenda($request, $kaldik), 403, 'Anda tidak memiliki akses untuk mengedit agenda ini.');

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $workUnits = $isGlobal ? WorkUnit::active()->orderBy('name')->get() : collect();
        $categoryOptions = $isGlobal
            ? Kaldik::CATEGORY_OPTIONS
            : [Kaldik::CATEGORY_AGENDA => Kaldik::CATEGORY_OPTIONS[Kaldik::CATEGORY_AGENDA]];

        return view('kaldik.edit', compact('kaldik', 'academicYears', 'workUnits', 'userId', 'isGlobal', 'categoryOptions'));
    }

    /**
     * Update the specified Kaldik/Agenda.
     */
    public function update(Request $request, string $userId, string $kaldikId)
    {
        // Warna otomatis dari jenis (legenda default) bila tidak diisi.
        if ($request->filled('type') && ! $request->filled('color')) {
            $request->merge(['color' => kaldik_default_color($request->input('type'))]);
        }

        $kaldik = Kaldik::findOrFail($kaldikId);
        $isGlobal = $this->isGlobalUser($request);
        $canKaldik = $this->canCreateKaldik($request);
        $canUnit = $this->canCreateUnitAgenda($request);
        $user = $request->user();

        abort_unless($this->canManageAgenda($request, $kaldik), 403, 'Anda tidak memiliki akses untuk mengubah agenda ini.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:kaldik,agenda_satuan,agenda_pribadi,agenda',
            'semester' => 'nullable|in:ganjil,genap',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'type' => 'nullable|in:'.implode(',', array_keys(Kaldik::TYPE_OPTIONS)),
            'color' => 'nullable|string|max:30',
            'work_unit_id' => 'nullable|exists:work_units,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
            'visibility' => 'nullable|in:satuan,pribadi',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $choice = $validated['category'];

        if ($choice === 'kaldik') {
            abort_unless($canKaldik, 403, 'Anda tidak berhak mengubah Kaldik pondok.');
            $validated['category'] = Kaldik::CATEGORY_KALDIK;
            $validated['visibility'] = Kaldik::VISIBILITY_SATUAN;
            $validated['work_unit_id'] = null;
            $validated['owner_user_id'] = null;
        } elseif ($choice === 'agenda_satuan' || ($choice === 'agenda' && ($validated['visibility'] ?? 'satuan') !== 'pribadi')) {
            abort_unless($canUnit, 403, 'Anda tidak berhak mengubah agenda satuan kerja.');
            $validated['category'] = Kaldik::CATEGORY_AGENDA;
            $validated['visibility'] = Kaldik::VISIBILITY_SATUAN;
            $validated['work_unit_id'] = $this->getUserWorkUnitId($request);
            $validated['owner_user_id'] = null;
        } else {
            $validated['category'] = Kaldik::CATEGORY_AGENDA;
            $validated['visibility'] = Kaldik::VISIBILITY_PRIBADI;
            $validated['work_unit_id'] = $this->getUserWorkUnitId($request);
            $validated['owner_user_id'] = $kaldik->owner_user_id ?: $user->id;
        }

        $kaldik->update($validated);

        // Notifikasi IN-APP GTK: agenda Kaldik diperbarui.
        if ($kaldik->wasChanged(['name', 'start_date', 'end_date', 'type'])) {
            try {
                $user = auth()->user();
                $targetWorkUnit = $kaldik->work_unit_id;

                if ($kaldik->category === Kaldik::CATEGORY_AGENDA && ! $targetWorkUnit) {
                    $targetWorkUnit = $user?->gtkWorkUnits()?->where('is_primary', true)->value('work_unit_id')
                        ?? $user?->gtkWorkUnits()?->value('work_unit_id');
                }

                app(\App\Services\NotificationUniversalService::class)->notifyGtk($targetWorkUnit, [
                    'module' => 'kaldik',
                    'reference_type' => 'kaldik',
                    'reference_id' => $kaldik->id,
                    'type' => 'info',
                    'action' => 'kaldik_updated',
                    'title' => 'Kaldik diperbarui: '.$kaldik->name,
                    'message' => 'Agenda '.($kaldik->type ?? 'kegiatan').' tanggal '
                        .($kaldik->start_date?->format('d/m/Y') ?? '-').' telah diperbarui.',
                    'action_url' => fn (string $recipientId) => route('user.kaldik.index', ['userId' => $recipientId]),
                    'action_text' => 'Lihat Kaldik',
                    'priority' => 'medium',
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Notif Kaldik update gagal: '.$e->getMessage());
            }
        }


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

        abort_unless($this->canManageAgenda($request, $kaldik), 403, 'Anda tidak memiliki akses untuk menghapus agenda ini.');

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

        abort_unless($this->canManageAgenda($request, $kaldik), 403);

        $kaldik->update(['is_active' => ! $kaldik->is_active]);

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
            ? '📅 Agenda Kegiatan Baru'
            : '📅 Kaldik Baru';

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
            $adminTUIds = GtkWorkUnit::where('work_unit_id', $kaldik->work_unit_id)
                ->whereHas('user', fn ($q) => $q->whereIn('id', usersHavingPermission('admin.tu.assessable')))
                ->pluck('user_id')
                ->toArray();
            $targetUserIds = array_merge($targetUserIds, $adminTUIds);
        }

        // Selalu kirim ke Super Admin & Administrator
        $adminIds = usersHavingPermission('general_admin.administrable');
        $targetUserIds = array_unique(array_merge($targetUserIds, $adminIds));

        // Hapus creator dari list agar tidak notifikasi ke dirinya sendiri
        $targetUserIds = array_filter($targetUserIds, fn ($id) => $id !== $creator->id);

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
