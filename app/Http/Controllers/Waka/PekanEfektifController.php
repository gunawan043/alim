<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\PekanEfektif;
use App\Services\PekanEfektifService;
use Illuminate\Http\Request;

class PekanEfektifController extends Controller
{
    public function __construct(private readonly PekanEfektifService $pekanService) {}

    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = PekanEfektif::with(['academicYear:id,name']);
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $pekanList = $query->orderBy('minggu_ke')
            ->orderBy('semester')
            ->paginate(20)->withQueryString();

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        // Hitung ringkasan minggu efektif per semester
        $summary = (clone $query)->reorder()
            ->selectRaw('semester, jenis, COUNT(*) as jumlah')
            ->groupBy('semester', 'jenis')
            ->get()
            ->groupBy('semester');

        // Ringkasan turunan Kalender Pendidikan untuk tahun ajaran & semester terpilih.
        $selectedAyId = $request->input('academic_year_id')
            ?: $academicYears->firstWhere('is_active', true)?->id
            ?: $academicYears->first()?->id;
        $selectedAy = $academicYears->firstWhere('id', $selectedAyId);
        $selectedSemester = (int) ($request->input('semester')
            ?: ($selectedAy?->semester === 'genap' ? 2 : 1));

        $ringkasan = ($schoolId && $selectedAyId)
            ? $this->pekanService->summary($schoolId, $selectedAyId, $selectedSemester)
            : null;

        return view('waka.pekan-efektif.index', compact(
            'pekanList',
            'academicYears',
            'summary',
            'ringkasan',
            'selectedAyId',
            'selectedSemester'
        ));
    }

    /**
     * Generate Pekan Efektif dari Kalender Pendidikan (sumber data tunggal).
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:1,2',
        ]);

        $schoolId = $request->attributes->get('schoolContextId');
        if (! $schoolId) {
            return back()->with('error', 'Konteks satuan pendidikan tidak ditemukan. Pilih sekolah terlebih dahulu.');
        }

        $result = $this->pekanService->generate(
            $schoolId,
            $validated['academic_year_id'],
            (int) $validated['semester'],
            $request->user()?->id
        );

        $summary = $result['summary'];

        return redirect()
            ->route('waka.pekan-efektif.index', [
                'academic_year_id' => $validated['academic_year_id'],
                'semester' => $validated['semester'],
            ])
            ->with(
                'success',
                "Pekan efektif berhasil digenerate dari Kalender Pendidikan: "
                ."{$summary['minggu_efektif']} minggu efektif, "
                ."{$summary['total_hari_efektif']} hari efektif, "
                ."{$summary['minggu_libur']} minggu libur."
            );
    }

    public function create(Request $request)
    {
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return view('waka.pekan-efektif.create', compact('academicYears'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:1,2',
            'minggu_ke' => 'required|integer|min:1|max:52',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jenis' => 'required|in:efektif,libur,ujian,kegiatan_sekolah,lainnya',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $validated['school_id'] = $request->attributes->get('schoolContextId');

        // Uniqueness lembut: tolak duplikat soft
        $duplicate = PekanEfektif::where('school_id', $validated['school_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('semester', $validated['semester'])
            ->where('minggu_ke', $validated['minggu_ke'])
            ->first();
        if ($duplicate) {
            return back()->withErrors([
                'minggu_ke' => 'Pekan ke-'.$validated['minggu_ke'].' semester '.$validated['semester'].' sudah ada.',
            ])->withInput();
        }

        PekanEfektif::create($validated);

        return redirect()->route('waka.pekan-efektif.index')
            ->with('success', 'Pekan efektif berhasil ditambahkan.');
    }

    public function show(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = PekanEfektif::with(['academicYear:id,name'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $pekan = $query->findOrFail($id);

        return view('waka.pekan-efektif.show', compact('pekan'));
    }

    public function edit(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = PekanEfektif::when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $pekan = $query->findOrFail($id);
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return view('waka.pekan-efektif.edit', compact('pekan', 'academicYears'));
    }

    public function update(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $pekan = PekanEfektif::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:1,2',
            'minggu_ke' => 'required|integer|min:1|max:52',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jenis' => 'required|in:efektif,libur,ujian,kegiatan_sekolah,lainnya',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $pekan->update($validated);

        return redirect()->route('waka.pekan-efektif.show', $pekan->id)
            ->with('success', 'Pekan efektif berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $pekan = PekanEfektif::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);
        $pekan->delete();

        return redirect()->route('waka.pekan-efektif.index')
            ->with('success', 'Pekan efektif berhasil dihapus.');
    }
}
