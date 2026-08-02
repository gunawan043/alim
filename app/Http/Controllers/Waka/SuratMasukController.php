<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\SuratMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuratMasukController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = SuratMasuk::query();
        if ($schoolId) {
            $query->where('school_id', $schoolId);
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
                    ->orWhere('pengirim', 'LIKE', "%{$search}%");
            });
        }

        $suratMasukList = $query->orderBy('tanggal_diterima', 'desc')
            ->paginate(15)->withQueryString();

        return view('waka.surat-masuk.index', compact('suratMasukList'));
    }

    public function create(Request $request)
    {
        return view('waka.surat-masuk.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_surat' => 'required|string|max:100',
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'required|date',
            'pengirim' => 'required|string|max:255',
            'perihal' => 'required|string|max:255',
            'file_lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'sifat' => 'required|in:rahasia,biasa,penting',
            'sifat_penyelesaian' => 'required|in:segera,biasa',
            'disposisi_to' => 'nullable|string|max:255',
            'disposisi_catatan' => 'nullable|string',
            'status' => 'required|in:baru,didisposisi,selesai',
        ]);

        if ($request->hasFile('file_lampiran')) {
            $validated['file_lampiran'] = $request->file('file_lampiran')
                ->store('surat/masuk', 'public');
        }

        $validated['school_id'] = $request->attributes->get('schoolContextId');

        SuratMasuk::create($validated);

        return redirect()->route('waka.surat-masuk.index')
            ->with('success', 'Surat masuk berhasil dicatat.');
    }

    public function show(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = SuratMasuk::when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $surat = $query->findOrFail($id);

        return view('waka.surat-masuk.show', compact('surat'));
    }

    public function edit(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = SuratMasuk::when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $surat = $query->findOrFail($id);

        return view('waka.surat-masuk.edit', compact('surat'));
    }

    public function update(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $surat = SuratMasuk::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);

        $validated = $request->validate([
            'nomor_surat' => 'required|string|max:100',
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'required|date',
            'pengirim' => 'required|string|max:255',
            'perihal' => 'required|string|max:255',
            'file_lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'sifat' => 'required|in:rahasia,biasa,penting',
            'sifat_penyelesaian' => 'required|in:segera,biasa',
            'disposisi_to' => 'nullable|string|max:255',
            'disposisi_catatan' => 'nullable|string',
            'status' => 'required|in:baru,didisposisi,selesai',
        ]);

        if ($request->hasFile('file_lampiran')) {
            // hapus lampiran lama bila ada
            if ($surat->file_lampiran && Storage::disk('public')->exists($surat->file_lampiran)) {
                Storage::disk('public')->delete($surat->file_lampiran);
            }
            $validated['file_lampiran'] = $request->file('file_lampiran')
                ->store('surat/masuk', 'public');
        }

        $surat->update($validated);

        return redirect()->route('waka.surat-masuk.show', $surat->id)
            ->with('success', 'Surat masuk berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $surat = SuratMasuk::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);

        if ($surat->file_lampiran && Storage::disk('public')->exists($surat->file_lampiran)) {
            Storage::disk('public')->delete($surat->file_lampiran);
        }
        $surat->delete();

        return redirect()->route('waka.surat-masuk.index')
            ->with('success', 'Surat masuk berhasil dihapus.');
    }
}
