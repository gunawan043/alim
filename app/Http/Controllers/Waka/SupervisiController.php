<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GtkProfile;
use App\Models\Supervisi;
use App\Models\SupervisiInstrumen;
use App\Models\SupervisiPenilaian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return view('waka.supervisi.index', compact('supervisiList', 'academicYears'));
    }

    public function create(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $gtks = $this->gtkOptionsForSchool($schoolId);
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return view('waka.supervisi.create', compact('gtks', 'academicYears'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gtk_id' => 'nullable|exists:gtk_profiles,id',
            'observer_id' => 'nullable|exists:gtk_profiles,id',
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

    protected function gtkOptionsForSchool(?string $schoolId)
    {
        if (! $schoolId) {
            return collect();
        }

        // Filter langsung via gtk_employments (gtk_profiles tidak punya school_id).
        // NUPY diambil lewat accessor `latest_nupy` (lazy) agar nilainya benar.
        $userIds = \App\Models\GtkEmployment::query()
            ->where('school_id', $schoolId)
            ->pluck('user_id');

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

        $gtks = $this->gtkOptionsForSchool($schoolId);
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return view('waka.supervisi.edit', compact('supervisi', 'gtks', 'academicYears'));
    }

    public function update(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $supervisi = Supervisi::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);

        $validated = $request->validate([
            'gtk_id' => 'nullable|exists:gtk_profiles,id',
            'observer_id' => 'nullable|exists:gtk_profiles,id',
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
