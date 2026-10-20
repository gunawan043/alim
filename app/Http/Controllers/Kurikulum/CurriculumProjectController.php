<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\CurriculumProject;
use App\Models\CurriculumProjectAssessment;
use App\Models\CurriculumProjectBankItem;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Models\SchoolCurriculumSetting;
use App\Services\CurriculumProfileFilterService;
use App\Services\CurriculumProfileService;
use App\Services\KurikulumAccess;
use App\Services\Pdf\CurriculumPdfStyle;
use Illuminate\Http\Request;
use Dompdf\Dompdf;
use Dompdf\Options;
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
        $profileScope = app(CurriculumProfileFilterService::class)->scope($request);
        $profileValues = $profile?->values() ?? [];
        $projectTypes = (array) $profile?->configValue('kokurikuler_options', []);
        if (empty($projectTypes)) {
            $projectTypes = [
                CurriculumProject::TYPE_P5 => 'P5 — Projek Penguatan Profil Pelajar Pancasila',
                CurriculumProject::TYPE_P5RA => 'P5RA — Projek Penguatan Profil Pelajar Pancasila Rahmatan lil Alamin',
            ];
        }

        $query = CurriculumProject::with(['coordinator:id,name', 'creator:id,name', 'curriculumProfile:id,code,name,authority'])
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('start_date')
            ->orderByDesc('created_at');

        app(CurriculumProfileFilterService::class)->apply($query, $profile?->id, $profileScope);
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

        $bankItems = CurriculumProjectBankItem::query()
            ->where(function ($query) use ($schoolId) {
                $query->where('school_id', $schoolId)->orWhereNull('school_id');
            })
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'type', 'kind', 'title', 'description', 'dimensions']);
        $bankThemes = $bankItems->where('kind', 'theme')->values();
        $bankActivities = $bankItems->where('kind', 'activity')->values();

        $canManage = $this->access->isKurikulumEditor($request->user());
        $setting = SchoolCurriculumSetting::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->first();

        return view('kurikulum.proyek.index', compact(
            'userId', 'schoolId', 'academicYears', 'academicYear', 'academicYearId',
            'semester', 'profile', 'labels', 'profileValues', 'projectTypes',
            'projects', 'stats', 'gradeLevels', 'canManage', 'setting',
            'bankThemes', 'bankActivities', 'profileScope'
        ));
    }

    public function show(Request $request, string $userId, string $id)
    {
        $project = CurriculumProject::with(['assessments.student:id,name,nis', 'assessments.assessor:id,name'])
            ->findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId && $project->school_id === $schoolId, 403, 'Akses ditolak.');

        $profile = $project->curriculumProfile
            ?? $this->profiles->resolve($project->school_id, $project->academic_year_id);
        $labels = $this->profiles->labels($profile);
        $profileValues = $profile?->values() ?? [];
        $dimensions = collect($project->dimensions ?? [])
            ->filter()
            ->mapWithKeys(fn ($key) => [$key => $profileValues[$key] ?? $key])
            ->all();
        if (empty($dimensions)) {
            $dimensions = $profileValues;
        }

        $students = Student::query()
            ->where('school_id', $project->school_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'nis']);

        $assessments = $project->assessments->sortBy(fn ($assessment) => $assessment->student?->name)->values();
        $average = $assessments->where('average', '>', 0)->avg('average');
        $dimensionAverages = collect(array_keys($dimensions))->mapWithKeys(function ($key) use ($assessments) {
            $values = $assessments->map(fn ($assessment) => (float) data_get($assessment->scores, $key, 0))->filter(fn ($value) => $value > 0);

            return [$key => $values->isEmpty() ? null : round($values->avg(), 2)];
        });

        $canManage = $this->access->isKurikulumEditor($request->user());
        $rubric = $this->rubricLabels();

        return view('kurikulum.proyek.show', compact(
            'userId', 'project', 'profile', 'labels', 'dimensions', 'students',
            'assessments', 'average', 'dimensionAverages', 'canManage', 'rubric'
        ));
    }

    public function assess(Request $request, string $userId, string $id)
    {
        $project = CurriculumProject::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId && $project->school_id === $schoolId, 403, 'Akses ditolak.');
        abort_unless($this->access->isKurikulumEditor($request->user()), 403, 'Hanya editor kurikulum yang dapat menilai projek.');

        $profile = $project->curriculumProfile
            ?? $this->profiles->resolve($project->school_id, $project->academic_year_id);
        $projectDimensions = array_values(array_filter((array) ($project->dimensions ?? []), fn ($key) => is_string($key) && $key !== ''));
        $allowedDimensions = $projectDimensions ?: array_keys($profile?->values() ?? []);

        $validated = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where('school_id', $schoolId)],
            'scores' => 'required|array|min:1',
            'scores.*' => 'nullable|integer|min:1|max:4',
            'notes' => 'nullable|string|max:2000',
            'evidence_note' => 'nullable|string|max:3000',
            'evidence_url' => 'nullable|url|max:500',
        ]);

        $scores = collect($validated['scores'])
            ->filter(fn ($value, $key) => $value !== null && ($allowedDimensions === [] || in_array($key, $allowedDimensions, true)))
            ->map(fn ($value) => (int) $value)
            ->all();

        abort_if(empty($scores), 422, 'Isi minimal satu skor dimensi.');

        $average = round(array_sum($scores) / count($scores), 2);

        CurriculumProjectAssessment::updateOrCreate(
            ['project_id' => $project->id, 'student_id' => $validated['student_id']],
            [
                'scores' => $scores,
                'average' => $average,
                'predicate' => CurriculumProjectAssessment::predicateFor($average),
                'notes' => $validated['notes'] ?? null,
                'evidence_note' => $validated['evidence_note'] ?? null,
                'evidence_url' => $validated['evidence_url'] ?? null,
                'assessed_by' => $request->user()->id,
            ]
        );

        return redirect()
            ->route('user.kurikulum.proyek.show', ['userId' => $userId, 'id' => $project->id])
            ->with('success', 'Penilaian projek disimpan.');
    }

    public function destroyAssessment(Request $request, string $userId, string $id, string $assessmentId)
    {
        $project = CurriculumProject::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId && $project->school_id === $schoolId, 403, 'Akses ditolak.');
        abort_unless($this->access->isKurikulumEditor($request->user()), 403, 'Hanya editor kurikulum yang dapat menghapus penilaian.');

        CurriculumProjectAssessment::where('project_id', $project->id)->findOrFail($assessmentId)->delete();

        return back()->with('success', 'Penilaian dihapus.');
    }

    public function print(Request $request, string $userId, string $id)
    {
        // Endpoint cetak lama dipertahankan agar tautan lama tetap bekerja,
        // tetapi hasilnya langsung PDF (bukan preview HTML).
        return $this->pdf($request, $userId, $id);
    }

    public function pdf(Request $request, string $userId, string $id)
    {
        $project = CurriculumProject::with(['assessments.student:id,name,nis', 'school', 'academicYear', 'coordinator:id,name'])
            ->findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId && $project->school_id === $schoolId, 403, 'Akses ditolak.');

        $profile = $project->curriculumProfile
            ?? $this->profiles->resolve($project->school_id, $project->academic_year_id);
        $labels = $this->profiles->labels($profile);
        $profileValues = $profile?->values() ?? [];
        $dimensions = collect($project->dimensions ?? [])
            ->filter()
            ->mapWithKeys(fn ($key) => [$key => $profileValues[$key] ?? $key])
            ->all();
        if (empty($dimensions)) {
            $dimensions = $profileValues;
        }

        $assessments = $project->assessments->sortBy(fn ($assessment) => $assessment->student?->name)->values();
        $average = $assessments->where('average', '>', 0)->avg('average');
        $rubric = $this->rubricLabels();
        $school = $project->school;
        $curriculumProfile = $profile;
        $curriculumLabels = $labels;

        $html = view('cetak.proyek', compact(
            'school', 'project', 'profile', 'curriculumProfile', 'labels', 'curriculumLabels',
            'dimensions', 'assessments', 'average', 'rubric'
        ))->render();

        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $fontDir = storage_path('fonts');
        if (! is_dir($fontDir)) {
            mkdir($fontDir, 0755, true);
        }
        $options->setFontDir($fontDir);
        $options->setFontCache($fontDir);

        $dompdf = new Dompdf($options);
        app(CurriculumPdfStyle::class)->registerCambria($dompdf);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $safe = preg_replace('/[^A-Za-z0-9\-]+/', '-', 'Laporan-Projek-'.$project->type_label.'-'.$project->title) ?: 'Laporan-Projek';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$safe.'.pdf"',
        ]);
    }

    private function rubricLabels(): array
    {
        return [
            1 => 'Belum Berkembang',
            2 => 'Mulai Berkembang',
            3 => 'Berkembang Baik',
            4 => 'Sangat Berkembang',
        ];
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
            'source' => 'manual',
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
