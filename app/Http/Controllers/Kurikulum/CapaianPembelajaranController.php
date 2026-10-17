<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Concerns\AssertsPeriodLock;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\CapaianPembelajaran;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\TujuanPembelajaran;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;

/**
 * CP — Capaian Pembelajaran (per mapel + fase + tahun ajaran).
 * Dikelola tim kurikulum; menjadi dasar penyusunan TP.
 */
class CapaianPembelajaranController extends Controller
{
    use AssertsPeriodLock;

    public function __construct(private readonly KurikulumAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        // ARSIP PERIODE: CP kini ber-versi tahun ajaran (baris lama NULL = fallback).
        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('name')->get();
        $activeAyId = $academicYears->firstWhere('is_active', true)?->id;
        $academicYearId = $request->input('academic_year_id', $activeAyId);
        $selectedAy = $academicYears->firstWhere('id', $academicYearId);
        $isArchiveAy = $academicYearId && $activeAyId && (string) $academicYearId !== (string) $activeAyId;

        $query = CapaianPembelajaran::query()
            ->with(['subject:id,name,code', 'creator:id,name'])
            ->forSchool($schoolId)
            ->forAcademicYear($academicYearId)
            ->orderByAcademicYearFirst()
            ->when(! $this->access->isKurikulumTeam($request->user()), function ($q) use ($request, $schoolId) {
                // Guru: hanya CP mapel yang diampu / serumpun.
                $viewable = $this->access->viewableSubjectIds($request->user(), $schoolId);
                $q->whereIn('subject_id', $viewable ?: ['-']);
            })
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->filled('fase'), fn ($q) => $q->where('fase', $request->fase))
            ->orderBy('fase')
            ->orderBy('urutan')
            ->orderBy('created_at');

        // Baris versi TA menutupi baris lama (NULL) dengan kunci logis yang sama.
        $cpList = $query->get()
            ->unique(fn ($cp) => $cp->subject_id.'|'.$cp->fase.'|'.($cp->elemen ?? ''))
            ->values();

        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->when(! $this->access->isKurikulumTeam($request->user()), function ($q) use ($request, $schoolId) {
                $viewable = $this->access->viewableSubjectIds($request->user(), $schoolId);
                $q->whereIn('id', $viewable ?: ['-']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // Opsi fase diambil dari data jenjang (tidak di-hardcode).
        $faseOptions = GradeLevel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNotNull('fase')
            ->where('fase', '!=', '')
            ->distinct()
            ->orderBy('fase')
            ->pluck('fase');

        $tpCounts = TujuanPembelajaran::query()
            ->active()
            ->bySchool($schoolId)
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->subject_id))
            ->get(['capaian_pembelajaran_id'])
            ->groupBy('capaian_pembelajaran_id')
            ->map->count();

        $cpUpdateUrlTemplate = route('user.kurikulum.cp.update', ['userId' => $userId, 'id' => '__ID__']);

        return view('kurikulum.cp.index', compact(
            'userId',
            'schoolId',
            'cpList',
            'subjects',
            'faseOptions',
            'tpCounts',
            'cpUpdateUrlTemplate',
            'academicYears',
            'academicYearId',
            'selectedAy',
            'isArchiveAy'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeManage($request);

        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'fase' => 'required|string|max:5',
            'elemen' => 'nullable|string|max:100',
            'deskripsi' => 'required|string|max:5000',
            'urutan' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // ARSIP PERIODE: CP baru menjadi versi TA terpilih (default TA aktif).
        $validated['academic_year_id'] = $validated['academic_year_id']
            ?? AcademicYear::where('is_active', true)->value('id');

        $this->assertPeriodNotLocked($validated['academic_year_id'], 'Capaian Pembelajaran');

        $validated['school_id'] = $schoolId;
        $validated['created_by'] = $request->user()->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        CapaianPembelajaran::create($validated);

        return redirect()
            ->route('user.kurikulum.cp.index', ['userId' => $userId, 'subject_id' => $validated['subject_id'], 'academic_year_id' => $validated['academic_year_id']])
            ->with('success', 'Capaian Pembelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $cp = CapaianPembelajaran::findOrFail($id);
        $this->authorizeManage($request, $cp->school_id);

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'fase' => 'required|string|max:5',
            'elemen' => 'nullable|string|max:100',
            'deskripsi' => 'required|string|max:5000',
            'urutan' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $this->assertPeriodNotLocked($cp->academic_year_id, 'Capaian Pembelajaran');
        $this->assertPeriodNotLocked($validated['academic_year_id'] ?? null, 'Capaian Pembelajaran');

        $validated['is_active'] = $request->boolean('is_active', true);
        $cp->update($validated);

        return redirect()
            ->route('user.kurikulum.cp.index', ['userId' => $userId, 'subject_id' => $cp->subject_id])
            ->with('success', 'Capaian Pembelajaran berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $cp = CapaianPembelajaran::findOrFail($id);
        $this->authorizeManage($request, $cp->school_id);

        $this->assertPeriodNotLocked($cp->academic_year_id, 'Capaian Pembelajaran');

        $cp->delete();

        return redirect()
            ->route('user.kurikulum.cp.index', ['userId' => $userId])
            ->with('success', 'Capaian Pembelajaran berhasil dihapus.');
    }

    /**
     * Hanya tim kurikulum (dan Super Admin) yang mengelola CP.
     */
    private function authorizeManage(Request $request, ?string $resourceSchoolId = null): void
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if ($resourceSchoolId && $schoolId && $resourceSchoolId !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        if (! $this->access->isKurikulumEditor($request->user())) {
            abort(403, 'Hanya tim kurikulum yang dapat mengelola Capaian Pembelajaran.');
        }
    }
}
