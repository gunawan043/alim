<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\CurriculumProject;
use App\Models\GradeLevel;
use App\Models\SchoolCurriculumSetting;
use App\Services\CurriculumProfileService;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurriculumProjectController extends Controller
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

        $semester = in_array($request->input('semester'), ['ganjil', 'genap'], true)
            ? $request->input('semester')
            : ($academicYear?->semester === 'genap' ? 'genap' : 'ganjil');

        $profile = $this->profiles->resolve($schoolId, $academicYearId);
        $labels = $this->profiles->labels($profile);
        $profileValues = $profile?->values() ?? [];
        $projectTypes = (array) $profile?->configValue('kokurikuler_options', []);
        if (empty($projectTypes)) {
            $projectTypes = [
                CurriculumProject::TYPE_P5 => 'P5 — Projek Penguatan Profil Pelajar Pancasila',
                CurriculumProject::TYPE_P5RA => 'P5RA — Projek Penguatan Profil Pelajar Pancasila Rahmatan lil Alamin',
            ];
        }

        $query = CurriculumProject::with(['coordinator:id,name', 'creator:id,name'])
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('start_date')
            ->orderByDesc('created_at');

        $projects = $query->get();
        $stats = [
            'total' => $projects->count(),
            'active' => $projects->where('status', CurriculumProject::STATUS_ACTIVE)->count(),
            'completed' => $projects->where('status', CurriculumProject::STATUS_COMPLETED)->count(),
            'draft' => $projects->where('status', CurriculumProject::STATUS_DRAFT)->count(),
        ];

        $gradeLevels = GradeLevel::query()
            ->where('school_id', $schoolId)
            ->orderBy('level')
            ->get(['id', 'name', 'fase']);

        $canManage = $this->access->isKurikulumEditor($request->user());
        $setting = SchoolCurriculumSetting::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->first();

        return view('kurikulum.proyek.index', compact(
            'userId', 'schoolId', 'academicYears', 'academicYear', 'academicYearId',
            'semester', 'profile', 'labels', 'profileValues', 'projectTypes',
            'projects', 'stats', 'gradeLevels', 'canManage', 'setting'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId, 404, 'Konteks satuan pendidikan tidak ditemukan.');
        abort_unless($this->access->isKurikulumEditor($request->user()), 403, 'Hanya editor kurikulum yang dapat membuat projek kokurikuler.');

        $validated = $this->validateProject($request, $schoolId);
        $profile = $this->profiles->resolve($schoolId, $validated['academic_year_id']);

        CurriculumProject::create(array_merge($validated, [
            'school_id' => $schoolId,
            'curriculum_profile_id' => $profile?->id,
            'activities' => $this->cleanActivities($validated['activities'] ?? []),
            'coordinator_id' => $request->user()->id,
            'created_by' => $request->user()->id,
        ]));

        return redirect()
            ->route('user.kurikulum.proyek.index', [
                'userId' => $userId,
                'academic_year_id' => $validated['academic_year_id'],
                'semester' => $validated['semester'],
            ])
            ->with('success', 'Projek kokurikuler berhasil dibuat.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $project = CurriculumProject::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId && $project->school_id === $schoolId, 403, 'Akses ditolak.');
        abort_unless($this->access->isKurikulumEditor($request->user()), 403, 'Hanya editor kurikulum yang dapat mengubah projek kokurikuler.');

        $validated = $this->validateProject($request, $schoolId);
        $profile = $this->profiles->resolve($schoolId, $validated['academic_year_id']);

        $project->update(array_merge($validated, [
            'curriculum_profile_id' => $profile?->id,
            'activities' => $this->cleanActivities($validated['activities'] ?? []),
        ]));

        return redirect()
            ->route('user.kurikulum.proyek.index', [
                'userId' => $userId,
                'academic_year_id' => $validated['academic_year_id'],
                'semester' => $validated['semester'],
            ])
            ->with('success', 'Projek kokurikuler diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $project = CurriculumProject::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId && $project->school_id === $schoolId, 403, 'Akses ditolak.');
        abort_unless($this->access->isKurikulumEditor($request->user()), 403, 'Hanya editor kurikulum yang dapat menghapus projek kokurikuler.');

        $project->delete();

        return back()->with('success', 'Projek kokurikuler dihapus.');
    }

    private function validateProject(Request $request, string $schoolId): array
    {
        $profile = $this->profiles->resolve($schoolId, $request->input('academic_year_id'));
        $allowedDimensions = array_keys($profile?->values() ?? []);
        $allowedTypes = array_keys((array) $profile?->configValue('kokurikuler_options', []));
        if (empty($allowedTypes)) {
            $allowedTypes = [CurriculumProject::TYPE_P5, CurriculumProject::TYPE_P5RA];
        }

        return $request->validate([
            'academic_year_id' => ['required', Rule::exists('academic_years', 'id')],
            'semester' => 'required|in:ganjil,genap',
            'type' => ['required', Rule::in($allowedTypes)],
            'title' => 'required|string|max:191',
            'theme' => 'nullable|string|max:191',
            'description' => 'nullable|string|max:5000',
            'dimensions' => 'nullable|array',
            'dimensions.*' => $allowedDimensions ? ['string', Rule::in($allowedDimensions)] : 'string|max:50',
            'grade_level_ids' => 'nullable|array',
            'grade_level_ids.*' => ['uuid', Rule::exists('grade_levels', 'id')->where('school_id', $schoolId)],
            'activities' => 'nullable|array',
            'activities.*.judul' => 'nullable|string|max:191',
            'activities.*.deskripsi' => 'nullable|string|max:2000',
            'activities.*.tanggal' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:draft,active,completed',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    private function cleanActivities(array $activities): array
    {
        return collect($activities)
            ->filter(fn ($activity) => filled($activity['judul'] ?? null) || filled($activity['deskripsi'] ?? null))
            ->map(fn ($activity) => [
                'judul' => trim((string) ($activity['judul'] ?? '')),
                'deskripsi' => trim((string) ($activity['deskripsi'] ?? '')),
                'tanggal' => $activity['tanggal'] ?? null,
            ])
            ->values()
            ->all();
    }
}
