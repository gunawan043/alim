<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\SuratKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuratKeluarController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);

        $base = SuratKeluar::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        $stats = [
            'total' => (clone $base)->count(),
            'draft' => (clone $base)->where('status', SuratKeluar::STATUS_DRAFT)->count(),
            'terkirim' => (clone $base)->where('status', SuratKeluar::STATUS_TERKIRIM)->count(),
            'dibatalkan' => (clone $base)->where('status', SuratKeluar::STATUS_DIBATALKAN)->count(),
        ];

        $query = SuratKeluar::query();
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($request->filled('dari')) {
            $query->whereDate('tanggal_surat', '>=', $request->input('dari'));
        }
        if ($request->filled('sampai')) {
            $query->whereDate('tanggal_surat', '<=', $request->input('sampai'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('sifat')) {
            $query->where('sifat', $request->sifat);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_surat', 'LIKE', "%{$search}%")
                    ->orWhere('perihal', 'LIKE', "%{$search}%")
                    ->orWhere('tujuan', 'LIKE', "%{$search}%");
            });
        }

        $suratKeluarList = $query->orderBy('tanggal_surat', 'desc')
            ->paginate(15)->withQueryString();

        return view('waka.surat-keluar.index', compact('suratKeluarList', 'stats'));
    }

    public function create(Request $request)
    {
        $templates = $this->templates($request);

        return view('waka.surat-keluar.create', compact('templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_surat' => 'nullable|string|max:100',
            'tanggal_surat' => 'required|date',
            'tanggal_kirim' => 'nullable|date',
            'tujuan' => 'required|string|max:255',
            'perihal' => 'required|string|max:255',
            'isi_surat' => 'nullable|string|max:20000',
            'file_lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'sifat' => 'required|in:rahasia,biasa,penting',
            'penandatangan' => 'nullable|string|max:255',
            'jabatan_penandatangan' => 'nullable|string|max:255',
            'status' => 'required|in:draft,terkirim,dibatalkan',
        ]);

        if ($request->hasFile('file_lampiran')) {
            $validated['file_lampiran'] = $request->file('file_lampiran')
                ->store('surat/keluar', 'public');
        }

        $schoolId = $this->resolveSchoolId($request);
        if (! $schoolId) {
            return back()->withInput()->with('error', 'Satuan pendidikan tidak terdeteksi. Pilih konteks satuan pendidikan lalu coba lagi.');
        }

        $validated['school_id'] = $schoolId;

        if (empty($validated['nomor_surat'])) {
            $validated['nomor_surat'] = app(\App\Services\SuratNumberService::class)->next($schoolId, 'keluar');
        }

        SuratKeluar::create($validated);

        return redirect()->route('waka.surat-keluar.index', ['userId' => $request->user()?->id])
            ->with('success', 'Surat keluar berhasil dibuat.');
    }

    public function show(Request $request, string $id)
    {
        $schoolId = $this->resolveSchoolId($request);
        $query = SuratKeluar::when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $surat = $query->findOrFail($id);

        return view('waka.surat-keluar.show', compact('surat'));
    }

    public function edit(Request $request, string $id)
    {
        $schoolId = $this->resolveSchoolId($request);
        $query = SuratKeluar::when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $surat = $query->findOrFail($id);
        $templates = $this->templates($request);

        return view('waka.surat-keluar.edit', compact('surat', 'templates'));
    }

    public function update(Request $request, string $id)
    {
        $schoolId = $this->resolveSchoolId($request);
        $surat = SuratKeluar::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);

        $validated = $request->validate([
            'nomor_surat' => 'required|string|max:100',
            'tanggal_surat' => 'required|date',
            'tanggal_kirim' => 'nullable|date',
            'tujuan' => 'required|string|max:255',
            'perihal' => 'required|string|max:255',
            'isi_surat' => 'nullable|string|max:20000',
            'file_lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'sifat' => 'required|in:rahasia,biasa,penting',
            'penandatangan' => 'nullable|string|max:255',
            'jabatan_penandatangan' => 'nullable|string|max:255',
            'status' => 'required|in:draft,terkirim,dibatalkan',
        ]);

        if ($request->hasFile('file_lampiran')) {
            if ($surat->file_lampiran && Storage::disk('public')->exists($surat->file_lampiran)) {
                Storage::disk('public')->delete($surat->file_lampiran);
            }
            $validated['file_lampiran'] = $request->file('file_lampiran')
                ->store('surat/keluar', 'public');
        }

        $surat->update($validated);

        return redirect()->route('waka.surat-keluar.show', ['userId' => $request->user()?->id, $surat->id])
            ->with('success', 'Surat keluar berhasil diperbarui.');
    }

    public function cetak(Request $request, string $id)
    {
        $schoolId = $this->resolveSchoolId($request);
        $surat = SuratKeluar::with('school')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        return app(\App\Services\Pdf\DocumentPdfService::class)->fromView(
            'waka.surat-keluar.cetak',
            compact('surat'),
            'Surat-Keluar-'.$surat->nomor_surat,
        );
    }

    public function destroy(Request $request, string $id)
    {
        $schoolId = $this->resolveSchoolId($request);
        $surat = SuratKeluar::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);

        if ($surat->file_lampiran && Storage::disk('public')->exists($surat->file_lampiran)) {
            Storage::disk('public')->delete($surat->file_lampiran);
        }
        $surat->delete();

        return redirect()->route('waka.surat-keluar.index', ['userId' => $request->user()?->id])
            ->with('success', 'Surat keluar berhasil dihapus.');
    }

    private function templates(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);

        return \App\Models\SuratTemplate::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('school_id', $schoolId)->orWhereNull('school_id'))
            ->orderBy('nama')
            ->get();
    }

    /**
     * Konteks satuan pendidikan: middleware → unit kerja pengguna.
     */
    private function resolveSchoolId(Request $request): ?string
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId) {
            return $schoolId;
        }

        return $request->user()?->employment?->school_id;
    }
}
