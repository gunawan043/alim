<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\CapaianPembelajaran;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\TujuanPembelajaran;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;

/**
 * CP — Capaian Pembelajaran (per mapel + fase).
 * Dikelola tim kurikulum; menjadi dasar penyusunan TP.
 */
class CapaianPembelajaranController extends Controller
{
    public function __construct(private readonly KurikulumAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = CapaianPembelajaran::query()
            ->with(['subject:id,name,code', 'creator:id,name'])
            ->forSchool($schoolId)
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->filled('fase'), fn ($q) => $q->where('fase', $request->fase))
            ->orderBy('fase')
            ->orderBy('urutan')
            ->orderBy('created_at');

        $cpList = $query->get();

        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
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

        return view('kurikulum.cp.index', compact(
            'userId',
            'schoolId',
            'cpList',
            'subjects',
            'faseOptions',
            'tpCounts'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeManage($request);

        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'fase' => 'required|string|max:5',
            'elemen' => 'nullable|string|max:100',
            'deskripsi' => 'required|string|max:5000',
            'urutan' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['school_id'] = $schoolId;
        $validated['created_by'] = $request->user()->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        CapaianPembelajaran::create($validated);

        return redirect()
            ->route('user.kurikulum.cp.index', ['userId' => $userId, 'subject_id' => $validated['subject_id']])
            ->with('success', 'Capaian Pembelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $cp = CapaianPembelajaran::findOrFail($id);
        $this->authorizeManage($request, $cp->school_id);

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'fase' => 'required|string|max:5',
            'elemen' => 'nullable|string|max:100',
            'deskripsi' => 'required|string|max:5000',
            'urutan' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

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

        if (! $this->access->isKurikulumTeam($request->user())) {
            abort(403, 'Hanya tim kurikulum yang dapat mengelola Capaian Pembelajaran.');
        }
    }
}
