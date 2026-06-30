<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Ekstrakurikuler;
use App\Models\EkstrakurikulerAnggota;
use App\Models\GtkProfile;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EkstrakurikulerController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = Ekstrakurikuler::withCount(['anggotaAktif as jumlah_anggota'])->with('gtk:id,name', 'gtk.latestEmployment:id,nupy');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                  ->orWhere('pembimbing', 'LIKE', "%{$search}%")
                  ->orWhere('lokasi', 'LIKE', "%{$search}%");
            });
        }

        $ekskulList = $query->orderBy('nama')->paginate(15)->withQueryString();
        $gtkProfiles = GtkProfile::whereNotNull('name')->orderBy('name')->get();

        return view('waka.ekstrakurikuler.index', compact('ekskulList', 'gtkProfiles'));
    }

    public function create(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $gtks = [];
        if ($schoolId) {
            $gtks = GtkProfile::where('school_id', $schoolId)->orderBy('name')->get();
        }
        return view('waka.ekstrakurikuler.create', compact('gtks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'              => 'required|string|max:255',
            'gtk_id'            => 'nullable|exists:gtk_profiles,id',
            'pembimbing'        => 'nullable|string|max:255',
            'hari'              => 'nullable|string|max:50',
            'jam_mulai'         => 'nullable|date_format:H:i',
            'jam_selesai'       => 'nullable|date_format:H:i|after:jam_mulai',
            'lokasi'            => 'nullable|string|max:255',
            'deskripsi'         => 'nullable|string',
            'tanggal_mulai'     => 'nullable|date',
            'tanggal_selesai'   => 'nullable|date|after_or_equal:tanggal_mulai',
            'status'            => 'required|in:aktif,berhenti',
            'kuota'             => 'nullable|integer|min:1',
        ]);

        $validated['school_id'] = $request->attributes->get('schoolContextId');
        if (empty($validated['gtk_id'])) {
            $validated['gtk_id'] = $request->pembimbing;
        }

        $ekskul = Ekstrakurikuler::create($validated);
        return redirect()->route('waka.ekstrakurikuler.show', $ekskul->id)
            ->with('success', 'Ekstrakurikuler berhasil ditambahkan.');
    }

    public function show(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = Ekstrakurikuler::with(['gtk:id,name', 'gtk.latestEmployment:id,nupy'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId));
        $ekskul = $query->findOrFail($id);
        $anggota = EkstrakurikulerAnggota::with(['student:id,name,nisn', 'ekstrakurikuler:id,nama'])
            ->where('ekstrakurikuler_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('waka.ekstrakurikuler.show', compact('ekskul', 'anggota'));
    }

    public function edit(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = Ekstrakurikuler::when($schoolId, fn($q) => $q->where('school_id', $schoolId));
        $ekskul = $query->findOrFail($id);

        $gtks = [];
        if ($schoolId) {
            $gtks = GtkProfile::where('school_id', $schoolId)->orderBy('name')->get();
        }

        return view('waka.ekstrakurikuler.edit', compact('ekskul', 'gtks'));
    }

    public function update(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $ekskul = Ekstrakurikuler::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->findOrFail($id);

        $validated = $request->validate([
            'nama'              => 'required|string|max:255',
            'gtk_id'            => 'nullable|exists:gtk_profiles,id',
            'pembimbing'        => 'nullable|string|max:255',
            'hari'              => 'nullable|string|max:50',
            'jam_mulai'         => 'nullable|date_format:H:i',
            'jam_selesai'       => 'nullable|date_format:H:i|after:jam_mulai',
            'lokasi'            => 'nullable|string|max:255',
            'deskripsi'         => 'nullable|string',
            'tanggal_mulai'     => 'nullable|date',
            'tanggal_selesai'   => 'nullable|date|after_or_equal:tanggal_mulai',
            'status'            => 'required|in:aktif,berhenti',
            'kuota'             => 'nullable|integer|min:1',
        ]);

        if (empty($validated['gtk_id'])) {
            $validated['gtk_id'] = null;
        }

        $ekskul->update($validated);
        return redirect()->route('waka.ekstrakurikuler.show', $ekskul->id)
            ->with('success', 'Ekstrakurikuler berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $ekskul = Ekstrakurikuler::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->findOrFail($id);
        $ekskul->delete();

        return redirect()->route('waka.ekstrakurikuler.index')
            ->with('success', 'Ekstrakurikuler berhasil dihapus.');
    }
}
