<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\CurriculumProfile;
use App\Models\School;
use App\Models\SchoolCurriculumSetting;
use App\Services\CurriculumProfileService;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;

class CurriculumProfileController extends Controller
{
    public function __construct(
        private readonly KurikulumAccess $access,
        private readonly CurriculumProfileService $profiles,
    ) {}

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId, 404, 'Konteks satuan pendidikan tidak ditemukan.');
        abort_unless($this->access->isKurikulumTeam($request->user()), 403, 'Hanya tim kurikulum yang dapat membuka profil kurikulum.');

        $school = School::findOrFail($schoolId);
        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeYear?->id);
        $academicYear = $academicYears->firstWhere('id', $academicYearId);

        $profileList = CurriculumProfile::query()
            ->active()
            ->orderByRaw("authority = 'kemenag' desc")
            ->orderBy('name')
            ->get();

        $setting = $this->profiles->setting($schoolId, $academicYearId);
        $resolvedProfile = $setting?->profile ?? $this->profiles->resolve($schoolId, $academicYearId);
        $labels = $this->profiles->labels($resolvedProfile);
        $canManage = $this->access->isKurikulumEditor($request->user());

        $kokurikulerOptions = $profileList
            ->flatMap(fn ($profile) => (array) $profile->configValue('kokurikuler_options', []))
            ->unique()
            ->all();

        return view('kurikulum.profil.index', compact(
            'userId', 'school', 'academicYears', 'academicYear', 'academicYearId',
            'profileList', 'setting', 'resolvedProfile', 'labels', 'canManage',
            'kokurikulerOptions'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId, 404, 'Konteks satuan pendidikan tidak ditemukan.');
        abort_unless($this->access->isKurikulumEditor($request->user()), 403, 'Hanya editor kurikulum yang dapat menetapkan profil kurikulum.');

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'curriculum_profile_id' => 'required|exists:curriculum_profiles,id',
            'kokurikuler_type' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:2000',
        ]);

        $existing = SchoolCurriculumSetting::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->first();

        if ($existing?->is_locked && (string) $existing->curriculum_profile_id !== (string) $validated['curriculum_profile_id']) {
            return back()->with('error', 'Profil kurikulum tahun ajaran ini sudah dikunci. Buka kunci terlebih dahulu sebelum mengganti profil.');
        }

        SchoolCurriculumSetting::updateOrCreate(
            [
                'school_id' => $schoolId,
                'academic_year_id' => $validated['academic_year_id'],
            ],
            [
                'curriculum_profile_id' => $validated['curriculum_profile_id'],
                'kokurikuler_type' => $validated['kokurikuler_type'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'updated_by' => $request->user()->id,
            ]
        );

        return redirect()
            ->route('user.kurikulum.profil.index', ['userId' => $userId, 'academic_year_id' => $validated['academic_year_id']])
            ->with('success', 'Profil kurikulum satuan pendidikan berhasil disimpan.');
    }
}
