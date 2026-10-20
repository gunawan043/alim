<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GtkProfile;
use App\Models\Supervisi;
use App\Models\SupervisiInstrumen;
use App\Models\SupervisiPengaturan;
use App\Models\SupervisiPenilaian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupervisiController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = Supervisi::with([
            'gtk:id,user_id',
            'gtk.user:id,name',
            'gtk.latestEmployment:user_id,nupy',
            'observer:id,user_id',
            'observer.user:id,name',
            'observer.latestEmployment:user_id,nupy',
            'academicYear:id,name',
        ]);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('supervisor_id')) {
            $query->where('observer_id', $request->supervisor_id);
        }
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }
        if ($request->filled('jenis_supervisi')) {
            $query->where('jenis_supervisi', $request->jenis_supervisi);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('gtk_name', 'LIKE', "%{$search}%")
                    ->orWhere('observer_name', 'LIKE', "%{$search}%")
                    ->orWhere('mata_pelajaran', 'LIKE', "%{$search}%");
            });
        }

        $supervisiList = $query->orderBy('tanggal_supervisi', 'desc')->paginate(15)->withQueryString();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        $baseQuery = Supervisi::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $statistics = [
            'total' => (clone $baseQuery)->count(),
            'terjadwal' => (clone $baseQuery)->where('status', Supervisi::STATUS_TERJADWAL)->count(),
            'selesai' => (clone $baseQuery)->where('status', Supervisi::STATUS_SELESAI)->count(),
            'avg_nilai' => (float) ((clone $baseQuery)->whereNotNull('nilai_akhir')->avg('nilai_akhir') ?? 0),
        ];

        $supervisorIds = (clone $baseQuery)->whereNotNull('observer_id')->distinct()->pluck('observer_id');
        $supervisors = GtkProfile::query()
            ->with('user:id,name')
            ->whereIn('id', $supervisorIds)
            ->get()
            ->sortBy(fn ($gtk) => mb_strtolower((string) $gtk->user?->name))
            ->values();

        return view('waka.supervisi.index', compact('supervisiList', 'academicYears', 'statistics', 'supervisors'));
    }

    public function create(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $formOptions = $this->formOptions($schoolId, $activeYearId);

        return view('waka.supervisi.create', array_merge(
            compact('academicYears', 'activeYearId'),
            $formOptions
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gtk_id' => 'required|uuid|exists:gtk_profiles,id',
            'observer_id' => 'required|uuid|exists:gtk_profiles,id|different:gtk_id',
            'gtk_name' => 'nullable|string|max:255',
            'observer_name' => 'nullable|string|max:255',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:1,2',
            'mata_pelajaran' => 'nullable|string|max:255',
            'tanggal_supervisi' => 'required|date',
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i|after:jam_mulai',
            'jenis_supervisi' => 'required|in:perangkat_pembelajaran,proses_pembelajaran,penilaian,lainnya',
            'tujuan' => 'nullable|string',
            'catatan_temuan' => 'nullable|string',
            'rekomendasi' => 'nullable|string',
            'tindak_lanjut' => 'nullable|string',
            'status' => 'required|in:terjadwal,berlangsung,selesai,dibatalkan',
        ]);

        $schoolId = $request->attributes->get('schoolContextId');
        if (! $schoolId) {
            return back()->withInput()->with('error', 'Konteks satuan pendidikan tidak ditemukan.');
        }
        $validated['school_id'] = $schoolId;

        $this->validateSupervisorPair($validated);
        $this->assertNoScheduleConflict($validated);

        // Kolom denormalisasi `gtk_name`/`observer_name` diisi dari relasi bila kosong,
        // karena gtk_profiles tidak menyimpan nama (nama ada di tabel users).
        if (empty($validated['gtk_name']) && ! empty($validated['gtk_id'])) {
            $validated['gtk_name'] = GtkProfile::with('user')->find($validated['gtk_id'])?->user?->name;
        }
        if (empty($validated['observer_name']) && ! empty($validated['observer_id'])) {
            $validated['observer_name'] = GtkProfile::with('user')->find($validated['observer_id'])?->user?->name;
        }

        Supervisi::create($validated);

        return redirect()->route('waka.supervisi.index')
            ->with('success', 'Data supervisi berhasil ditambahkan.');
    }

    /**
     * Daftar GTK pada satuan pendidikan (gtk_profiles tidak punya school_id —
     * filter lewat gtk_employments.school_id), urut nama user.
     */
    /**
     * Form penilaian supervisi: item instrumen (dengan poin) → skor A–D + Ya/Tidak,
     * jumlah per bagian, jumlah keseluruhan, dan ketercapaian.
     */
    public function penilaian(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $supervisi = Supervisi::with(['gtk.user', 'observer.user', 'academicYear', 'instrumen.bagian.items.subItems'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        // Instrumen: dari query (dipilih operator) atau yang tersimpan di supervisi.
        $instrumen = $request->filled('instrumen_id')
            ? SupervisiInstrumen::with(['bagian.items.subItems'])->find($request->input('instrumen_id'))
            : $supervisi->instrumen;

        $instrumenOptions = SupervisiInstrumen::query()
            ->where('is_active', true)
            ->where(function ($q) use ($schoolId) {
                $q->whereNull('school_id');
                if ($schoolId) {
                    $q->orWhere('school_id', $schoolId);
                }
            })
            ->orderByRaw('school_id IS NULL')
            ->orderBy('nama')
            ->get();

        $penilaian = $supervisi->penilaian()->get()->keyBy('item_id');

        return view('waka.supervisi.penilaian', compact('supervisi', 'instrumen', 'instrumenOptions', 'penilaian'));
    }

    public function penilaianStore(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $supervisi = Supervisi::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);

        $validated = $request->validate([
            'instrumen_id' => 'required|exists:supervisi_instrumen,id',
            'catatan_temuan' => 'nullable|string',
            'rekomendasi' => 'nullable|string',
            'tindak_lanjut' => 'nullable|string',
            'skor' => 'nullable|array',
            'skor.*' => 'nullable|integer|min:0|max:4',
            'kondisi' => 'nullable|array',
            'kondisi.*' => 'nullable|in:ya,tidak',
            'item_catatan' => 'nullable|array',
            'item_catatan.*' => 'nullable|string|max:500',
        ]);

        $instrumen = SupervisiInstrumen::with(['bagian.items.subItems'])->findOrFail($validated['instrumen_id']);

        $items = $instrumen->bagian
            ->flatMap(fn ($bagian) => $bagian->items)
            ->flatMap(fn ($item) => collect([$item])->merge($item->subItems));

        $skorInput = $validated['skor'] ?? [];
        $kondisiInput = $validated['kondisi'] ?? [];
        $catatanInput = $validated['item_catatan'] ?? [];

        $total = 0.0;
        $maks = 0.0;

        DB::transaction(function () use ($supervisi, $instrumen, $items, $skorInput, $kondisiInput, $catatanInput, &$total, &$maks) {
            foreach ($items as $item) {
                $skor = $skorInput[$item->id] ?? null;
                $kondisi = $kondisiInput[$item->id] ?? null;
                $catatan = $catatanInput[$item->id] ?? null;

                SupervisiPenilaian::updateOrCreate(
                    ['supervisi_id' => $supervisi->id, 'item_id' => $item->id],
                    [
                        'skor' => ($skor === '' || $skor === null) ? null : (int) $skor,
                        'kondisi' => $kondisi === null ? null : $kondisi === 'ya',
                        'catatan' => $catatan ?: null,
                    ],
                );

                if ($skor !== null && $skor !== '') {
                    $total += (int) $skor;
                    $maks += (float) $item->bobot;
                }
            }

            $nilai = $maks > 0 ? round($total / $maks * 100, 2) : null;

            $supervisi->update([
                'instrumen_id' => $instrumen->id,
                'skor_total' => $total,
                'skor_maks' => $maks,
                'nilai_akhir' => $nilai,
                'ketercapaian' => $this->ketercapaianFor($nilai),
                'catatan_temuan' => $validated['catatan_temuan'] ?? null,
                'rekomendasi' => $validated['rekomendasi'] ?? null,
                'tindak_lanjut' => $validated['tindak_lanjut'] ?? null,
            ]);
        });

        $fresh = $supervisi->fresh();

        return redirect()
            ->route('waka.supervisi.show', $supervisi->id)
            ->with('success', 'Penilaian supervisi tersimpan. Nilai: '.number_format((float) $fresh->nilai_akhir, 2).' ('.($fresh->ketercapaian ?? '-').').');
    }

    /**
     * Cetak instrumen supervisi terisi (format lembar).
     */
    public function cetak(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $supervisi = Supervisi::with(['gtk.user', 'observer.user', 'academicYear', 'instrumen.bagian.items.subItems', 'school'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        $penilaian = $supervisi->penilaian()->get()->keyBy('item_id');

        return view('waka.supervisi.cetak', compact('supervisi', 'penilaian'));
    }

    /**
     * Ketercapaian: 86–100 Amat baik · 70–85 Baik · 55–69 Cukup · <55 Kurang.
     */
    private function ketercapaianFor(?float $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        return match (true) {
            $nilai >= 86 => 'Amat baik',
            $nilai >= 70 => 'Baik',
            $nilai >= 55 => 'Cukup',
            default => 'Kurang',
        };
    }

    /**
     * Opsi form: supervisor dan guru dibatasi oleh pengaturan supervisor.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(?string $schoolId, ?string $yearId = null): array
    {
        $yearId = $yearId ?: AcademicYear::where('is_active', true)->value('id');

        $settings = SupervisiPengaturan::query()
            ->with([
                'supervisor.user:id,name',
                'supervisor.latestEmployment:id,user_id,nupy,jabatan',
                'supervisee.user:id,name',
                'supervisee.latestEmployment:id,user_id,nupy,jabatan',
            ])
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->get();

        $hasSettings = $settings->isNotEmpty();

        if ($hasSettings) {
            $supervisorGtks = $settings->pluck('supervisor')->filter()->unique('id')->values();
            $superviseeGtks = $settings->pluck('supervisee')->filter()->unique('id')->values();
        } else {
            $supervisorGtks = $this->gtkOptionsForSchool($schoolId);
            $superviseeGtks = $supervisorGtks;
        }

        $supervisorPairs = [];
        foreach ($settings as $setting) {
            $supervisorPairs[$setting->supervisor_gtk_id][] = $setting->supervisee_gtk_id;
        }

        $currentProfileId = GtkProfile::where('user_id', auth()->id())->value('id');
        $defaultSupervisorId = $supervisorGtks->contains('id', $currentProfileId)
            ? $currentProfileId
            : $supervisorGtks->first()?->id;

        return compact(
            'supervisorGtks',
            'superviseeGtks',
            'supervisorPairs',
            'hasSettings',
            'defaultSupervisorId'
        );
    }

    /**
     * Pastikan pasangan supervisor → guru memang diatur untuk tahun ajaran ini.
     */
    protected function validateSupervisorPair(array $data): void
    {
        $this->ensureGtkBelongsToSchool($data['school_id'], $data['observer_id']);
        $this->ensureGtkBelongsToSchool($data['school_id'], $data['gtk_id']);

        $settingsQuery = SupervisiPengaturan::query()
            ->where('school_id', $data['school_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('is_active', true);

        if (! (clone $settingsQuery)->exists()) {
            return;
        }

        $pairExists = (clone $settingsQuery)
            ->where('supervisor_gtk_id', $data['observer_id'])
            ->where('supervisee_gtk_id', $data['gtk_id'])
            ->exists();

        if (! $pairExists) {
            throw ValidationException::withMessages([
                'gtk_id' => 'Pasangan supervisor dan guru ini belum diatur. Buka Pengaturan Supervisor terlebih dahulu.',
            ]);
        }
    }

    /**
     * Cegah supervisor/guru memiliki dua jadwal pada rentang waktu yang sama.
     */
    protected function assertNoScheduleConflict(array $data, ?string $ignoreId = null): void
    {
        $start = $data['jam_mulai'] ?? null;
        $end = $data['jam_selesai'] ?? null;

        if (! $start || ! $end) {
            return;
        }

        $conflict = Supervisi::query()
            ->where('school_id', $data['school_id'])
            ->whereDate('tanggal_supervisi', $data['tanggal_supervisi'])
            ->where('status', '!=', Supervisi::STATUS_DIBATALKAN)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($data) {
                $q->where('observer_id', $data['observer_id'])
                    ->orWhere('gtk_id', $data['gtk_id']);
            })
            ->where(function ($q) use ($start, $end) {
                $q->where('jam_mulai', '<', $end)
                    ->where('jam_selesai', '>', $start);
            })
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'tanggal_supervisi' => 'Jadwal bentrok: supervisor atau guru yang dipilih sudah memiliki supervisi pada jam tersebut.',
            ]);
        }
    }

    protected function ensureGtkBelongsToSchool(string $schoolId, string $gtkProfileId): void
    {
        $userIds = GtkProfile::query()->whereKey($gtkProfileId)->pluck('user_id');
        $belongsToSchool = app(\App\Models\GtkEmployment::class)::query()
            ->where('school_id', $schoolId)
            ->whereIn('user_id', $userIds)
            ->exists()
            || app(\App\Models\GtkWorkUnit::class)::query()
                ->whereIn('user_id', $userIds)
                ->whereHas('workUnit.school', fn ($q) => $q->where('id', $schoolId))
                ->exists();

        if (! $belongsToSchool) {
            throw ValidationException::withMessages([
                'gtk_id' => 'GTK yang dipilih tidak terdaftar pada satuan pendidikan ini.',
            ]);
        }
    }

    protected function gtkOptionsForSchool(?string $schoolId)
    {
        if (! $schoolId) {
            return collect();
        }

        // Filter langsung via gtk_employments (gtk_profiles tidak punya school_id).
        // NUPY diambil lewat accessor `latest_nupy` (lazy) agar nilainya benar.
        $userIds = \App\Models\GtkEmployment::query()
            ->where('school_id', $schoolId)
            ->pluck('user_id')
            ->merge(
                \App\Models\GtkWorkUnit::query()
                    ->whereHas('workUnit.school', fn ($q) => $q->where('id', $schoolId))
                    ->pluck('user_id')
            )
            ->unique();

        return GtkProfile::query()
            ->with('user:id,name')
            ->whereIn('user_id', $userIds)
            ->get()
            ->sortBy(fn ($gtk) => mb_strtolower((string) $gtk->user?->name))
            ->values();
    }

    public function show(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = Supervisi::with([
            'gtk:id,user_id',
            'gtk.user:id,name',
            'gtk.latestEmployment:user_id,nupy',
            'observer:id,user_id',
            'observer.user:id,name',
            'observer.latestEmployment:user_id,nupy',
            'academicYear:id,name',
        ])->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $supervisi = $query->findOrFail($id);

        return view('waka.supervisi.show', compact('supervisi'));
    }

    public function edit(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = Supervisi::when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $supervisi = $query->findOrFail($id);

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYearId = $supervisi->academic_year_id ?: AcademicYear::where('is_active', true)->value('id');
        $formOptions = $this->formOptions($schoolId, $activeYearId);

        // Pastikan pasangan yang sedang diedit tetap tersedia meskipun
        // pengaturannya sudah dinonaktifkan/diubah.
        if ($supervisi->observer_id && ! $formOptions['supervisorGtks']->contains('id', $supervisi->observer_id)) {
            $currentSupervisor = GtkProfile::with('user:id,name')->find($supervisi->observer_id);
            if ($currentSupervisor) {
                $formOptions['supervisorGtks']->push($currentSupervisor);
            }
        }
        if ($supervisi->gtk_id && ! $formOptions['superviseeGtks']->contains('id', $supervisi->gtk_id)) {
            $currentSupervisee = GtkProfile::with('user:id,name')->find($supervisi->gtk_id);
            if ($currentSupervisee) {
                $formOptions['superviseeGtks']->push($currentSupervisee);
            }
        }
        if ($supervisi->observer_id && $supervisi->gtk_id) {
            $formOptions['supervisorPairs'][$supervisi->observer_id] = array_values(array_unique(array_merge(
                $formOptions['supervisorPairs'][$supervisi->observer_id] ?? [],
                [$supervisi->gtk_id]
            )));
        }

        return view('waka.supervisi.edit', array_merge(
            compact('supervisi', 'academicYears', 'activeYearId'),
            $formOptions
        ));
    }

    public function update(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $supervisi = Supervisi::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);

        $validated = $request->validate([
            'gtk_id' => 'required|uuid|exists:gtk_profiles,id',
            'observer_id' => 'required|uuid|exists:gtk_profiles,id|different:gtk_id',
            'gtk_name' => 'nullable|string|max:255',
            'observer_name' => 'nullable|string|max:255',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:1,2',
            'mata_pelajaran' => 'nullable|string|max:255',
            'tanggal_supervisi' => 'required|date',
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i|after:jam_mulai',
            'jenis_supervisi' => 'required|in:perangkat_pembelajaran,proses_pembelajaran,penilaian,lainnya',
            'tujuan' => 'nullable|string',
            'catatan_temuan' => 'nullable|string',
            'rekomendasi' => 'nullable|string',
            'tindak_lanjut' => 'nullable|string',
            'status' => 'required|in:terjadwal,berlangsung,selesai,dibatalkan',
        ]);

        $validated['school_id'] = $schoolId;
        $this->validateSupervisorPair($validated);
        $this->assertNoScheduleConflict($validated, $supervisi->id);

        if (empty($validated['gtk_name']) && ! empty($validated['gtk_id'])) {
            $validated['gtk_name'] = GtkProfile::with('user')->find($validated['gtk_id'])?->user?->name;
        }
        if (empty($validated['observer_name']) && ! empty($validated['observer_id'])) {
            $validated['observer_name'] = GtkProfile::with('user')->find($validated['observer_id'])?->user?->name;
        }

        $supervisi->update($validated);

        return redirect()->route('waka.supervisi.show', $supervisi->id)
            ->with('success', 'Data supervisi berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $supervisi = Supervisi::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);
        $supervisi->delete();

        return redirect()->route('waka.supervisi.index')
            ->with('success', 'Data supervisi berhasil dihapus.');
    }
}
