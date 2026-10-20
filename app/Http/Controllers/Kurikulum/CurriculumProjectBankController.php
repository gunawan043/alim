<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\CurriculumProjectBankItem;
use App\Services\CurriculumProfileService;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurriculumProjectBankController extends Controller
{
    public function __construct(
        private readonly KurikulumAccess $access,
        private readonly CurriculumProfileService $profiles,
    ) {}

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId, 404, 'Konteks satuan pendidikan tidak ditemukan.');

        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeYear?->id);
        $academicYear = $academicYears->firstWhere('id', $academicYearId);

        $profile = $this->profiles->resolve($schoolId, $academicYearId);
        $labels = $this->profiles->labels($profile);
        $values = $profile?->values() ?? [];
        $projectTypes = (array) $profile?->configValue('kokurikuler_options', []);
        if (empty($projectTypes)) {
            $projectTypes = ['p5' => 'P5', 'p5ra' => 'P5RA'];
        }

        $items = CurriculumProjectBankItem::with('creator:id,name')
            ->where(function ($query) use ($schoolId) {
                $query->where('school_id', $schoolId)->orWhereNull('school_id');
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('kind'), fn ($q) => $q->where('kind', $request->input('kind')))
            ->orderBy('kind')
            ->orderBy('title')
            ->get();

        $canManage = $this->access->isKurikulumEditor($request->user());

        return view('kurikulum.proyek.bank', compact(
            'userId', 'academicYears', 'academicYear', 'academicYearId',
            'profile', 'labels', 'values', 'projectTypes', 'items', 'canManage'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId, 404, 'Konteks satuan pendidikan tidak ditemukan.');
        abort_unless($this->access->isKurikulumEditor($request->user()), 403, 'Hanya editor kurikulum yang dapat mengelola bank projek.');

        $validated = $this->validateItem($request, $schoolId);
        $profile = $this->profiles->resolve($schoolId, $validated['academic_year_id']);

        CurriculumProjectBankItem::create([
            'school_id' => $schoolId,
            'curriculum_profile_id' => $profile?->id,
            'type' => $validated['type'],
            'kind' => $validated['kind'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'dimensions' => array_values(array_unique($validated['dimensions'] ?? [])),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('user.kurikulum.proyek.bank.index', ['userId' => $userId, 'academic_year_id' => $validated['academic_year_id']])
            ->with('success', 'Item bank projek ditambahkan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $item = CurriculumProjectBankItem::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId && $item->school_id === $schoolId, 403, 'Akses ditolak.');
        abort_unless($this->access->isKurikulumEditor($request->user()), 403, 'Hanya editor kurikulum yang dapat mengelola bank projek.');

        $validated = $this->validateItem($request, $schoolId);
        $profile = $this->profiles->resolve($schoolId, $validated['academic_year_id']);

        $item->update([
            'curriculum_profile_id' => $profile?->id,
            'type' => $validated['type'],
            'kind' => $validated['kind'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'dimensions' => array_values(array_unique($validated['dimensions'] ?? [])),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('user.kurikulum.proyek.bank.index', ['userId' => $userId, 'academic_year_id' => $validated['academic_year_id']])
            ->with('success', 'Item bank projek diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $item = CurriculumProjectBankItem::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId && $item->school_id === $schoolId, 403, 'Akses ditolak.');
        abort_unless($this->access->isKurikulumEditor($request->user()), 403, 'Hanya editor kurikulum yang dapat menghapus bank projek.');

        $item->delete();

        return back()->with('success', 'Item bank projek dihapus.');
    }

    private function validateItem(Request $request, string $schoolId): array
    {
        $profile = $this->profiles->resolve($schoolId, $request->input('academic_year_id'));
        $allowedDimensions = array_keys($profile?->values() ?? []);
        $allowedTypes = array_keys((array) $profile?->configValue('kokurikuler_options', []));
        if (empty($allowedTypes)) {
            $allowedTypes = ['p5', 'p5ra'];
        }

        return $request->validate([
            'academic_year_id' => ['required', Rule::exists('academic_years', 'id')],
            'type' => ['required', Rule::in($allowedTypes)],
            'kind' => 'required|in:theme,activity',
            'title' => 'required|string|max:191',
            'description' => 'nullable|string|max:3000',
            'dimensions' => 'nullable|array',
            'dimensions.*' => $allowedDimensions ? ['string', Rule::in($allowedDimensions)] : 'string|max:50',
            'is_active' => 'boolean',
        ]);
    }
}
