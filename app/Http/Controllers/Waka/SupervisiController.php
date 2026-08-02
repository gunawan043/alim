<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GtkProfile;
use App\Models\Supervisi;
use Illuminate\Http\Request;

class SupervisiController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = Supervisi::with([
            'gtk:id,name',
            'gtk.latestEmployment:id,nupy',
            'observer:id,name',
            'observer.latestEmployment:id,nupy',
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
        $gtks = [];
        if ($schoolId) {
            $gtks = GtkProfile::where('school_id', $schoolId)->orderBy('name')->get();
        }
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

        $validated['school_id'] = $request->attributes->get('schoolContextId');

        Supervisi::create($validated);

        return redirect()->route('waka.supervisi.index')
            ->with('success', 'Data supervisi berhasil ditambahkan.');
    }

    public function show(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = Supervisi::with([
            'gtk:id,name',
            'gtk.latestEmployment:id,nupy',
            'observer:id,name',
            'observer.latestEmployment:id,nupy',
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

        $gtks = [];
        if ($schoolId) {
            $gtks = GtkProfile::where('school_id', $schoolId)->orderBy('name')->get();
        }
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
