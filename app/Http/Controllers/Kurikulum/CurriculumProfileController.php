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

        // Profil Kurikulum memuat informasi kurikulum yang boleh dibaca seluruh
        // GTK pada satuan pendidikan. Pengaturan tetap dibatasi oleh $canManage.
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
        $canManage = $this->access->isKurikulumTeam($request->user());
        $curriculumUpdates = (array) config('curriculum_updates.items', []);
        $curriculumUpdatesAt = (string) config('curriculum_updates.updated_at', '');
        $semester = $academicYear?->semester === 'genap' ? 'genap' : 'ganjil';

        $curriculumFlow = [
            [
                'title' => 'Profil Kurikulum',
                'description' => 'Tetapkan profil, label, dan jenis kokurikuler.',
                'icon' => 'ri-settings-4-line',
                'url' => route('user.kurikulum.profil.index', ['userId' => $userId, 'academic_year_id' => $academicYearId]),
            ],
            [
                'title' => 'Kalender Pendidikan',
                'description' => 'Atur hari efektif, libur, dan agenda sekolah.',
                'icon' => 'ri-calendar-event-line',
                'url' => route('user.kaldik.index', ['userId' => $userId]),
            ],
            [
                'title' => 'Pekan Efektif',
                'description' => 'Hitung minggu dan JP efektif per semester.',
                'icon' => 'ri-calendar-todo-line',
                'url' => route('user.pekan-efektif.index', ['userId' => $userId, 'academic_year_id' => $academicYearId]),
            ],
            [
                'title' => 'Peta Kurikulum & KKTP',
                'description' => 'Cek mapel × tingkat, JP, dan nilai N-KKTP.',
                'icon' => 'ri-table-line',
                'url' => route('user.kurikulum.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]),
            ],
            [
                'title' => 'Capaian Pembelajaran',
                'description' => 'Rumuskan CP sesuai fase dan mata pelajaran.',
                'icon' => 'ri-flag-2-line',
                'url' => route('user.kurikulum.cp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]),
            ],
            [
                'title' => 'Tujuan Pembelajaran',
                'description' => 'Turunkan CP menjadi tujuan yang terukur.',
                'icon' => 'ri-list-check-2',
                'url' => route('user.kurikulum.tp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]),
            ],
            [
                'title' => 'ATP',
                'description' => 'Urutkan TP dan alokasikan JP pembelajaran.',
                'icon' => 'ri-route-line',
                'url' => route('user.kurikulum.atp.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]),
            ],
            [
                'title' => 'PROTA',
                'description' => 'Distribusikan ATP ke seluruh tahun ajaran.',
                'icon' => 'ri-calendar-schedule-line',
                'url' => route('user.kurikulum.prota.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]),
            ],
            [
                'title' => 'PROSEM',
                'description' => 'Susun rencana pembelajaran per minggu efektif.',
                'icon' => 'ri-calendar-2-line',
                'url' => route('user.kurikulum.prosem.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]),
            ],
            [
                'title' => 'Perangkat Pembelajaran',
                'description' => 'Buat RPM/Modul Ajar, review, lalu finalkan.',
                'icon' => 'ri-booklet-line',
                'url' => route('user.kurikulum.perangkat.index', ['userId' => $userId, 'academic_year_id' => $academicYearId, 'semester' => $semester]),
                'final' => true,
            ],
        ];

        $kokurikulerOptions = $profileList
            ->flatMap(fn ($profile) => (array) $profile->configValue('kokurikuler_options', []))
            ->unique()
            ->all();

        return view('kurikulum.profil.index', compact(
            'userId', 'school', 'academicYears', 'academicYear', 'academicYearId',
            'profileList', 'setting', 'resolvedProfile', 'labels', 'canManage',
            'kokurikulerOptions', 'curriculumUpdates', 'curriculumUpdatesAt', 'curriculumFlow'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId, 404, 'Konteks satuan pendidikan tidak ditemukan.');
        abort_unless($this->access->isKurikulumTeam($request->user()), 403, 'Hanya KSP, Wakil Kepala, atau tim kurikulum yang dapat menetapkan profil kurikulum.');

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
