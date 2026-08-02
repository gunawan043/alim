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
        $schoolId = $request->attributes->get('schoolContextId');

        $query = SuratKeluar::query();
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
                    ->orWhere('tujuan', 'LIKE', "%{$search}%");
            });
        }

        $suratKeluarList = $query->orderBy('tanggal_surat', 'desc')
            ->paginate(15)->withQueryString();

        return view('waka.surat-keluar.index', compact('suratKeluarList'));
    }

    public function create(Request $request)
    {
        return view('waka.surat-keluar.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_surat' => 'required|string|max:100',
            'tanggal_surat' => 'required|date',
            'tanggal_kirim' => 'nullable|date',
            'tujuan' => 'required|string|max:255',
            'perihal' => 'required|string|max:255',
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

        $validated['school_id'] = $request->attributes->get('schoolContextId');

        SuratKeluar::create($validated);

        return redirect()->route('waka.surat-keluar.index')
            ->with('success', 'Surat keluar berhasil dibuat.');
    }

    public function show(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = SuratKeluar::when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $surat = $query->findOrFail($id);

        return view('waka.surat-keluar.show', compact('surat'));
    }

    public function edit(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = SuratKeluar::when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $surat = $query->findOrFail($id);

        return view('waka.surat-keluar.edit', compact('surat'));
    }

    public function update(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $surat = SuratKeluar::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);

        $validated = $request->validate([
            'nomor_surat' => 'required|string|max:100',
            'tanggal_surat' => 'required|date',
            'tanggal_kirim' => 'nullable|date',
            'tujuan' => 'required|string|max:255',
            'perihal' => 'required|string|max:255',
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

        return redirect()->route('waka.surat-keluar.show', $surat->id)
            ->with('success', 'Surat keluar berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $surat = SuratKeluar::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);

        if ($surat->file_lampiran && Storage::disk('public')->exists($surat->file_lampiran)) {
            Storage::disk('public')->delete($surat->file_lampiran);
        }
        $surat->delete();

        return redirect()->route('waka.surat-keluar.index')
            ->with('success', 'Surat keluar berhasil dihapus.');
    }
}
