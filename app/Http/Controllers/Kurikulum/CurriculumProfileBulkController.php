<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\CurriculumProfile;
use App\Models\PerangkatPembelajaran;
use App\Models\Prosem;
use App\Models\Prota;
use App\Models\School;
use App\Models\SchoolCurriculumSetting;
use App\Models\TujuanPembelajaran;
use App\Services\TahfidzAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CurriculumProfileBulkController extends Controller
{
    public function __construct(private readonly TahfidzAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $user = $request->user();
        abort_unless($this->access->canViewAllSchools($user), 403, 'Hanya Departemen/Super Admin yang dapat memetakan lintas sekolah.');

        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeYear?->id);
        $academicYear = $academicYears->firstWhere('id', $academicYearId);

        $profiles = CurriculumProfile::query()->active()->orderBy('name')->get();
        $schools = School::query()->where('is_active', true)->with('workUnit:id,name')->orderBy('name')->get();
        $settings = SchoolCurriculumSetting::query()
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->keyBy('school_id');

        $counts = $this->documentCounts($academicYearId);

        return view('schools-global.kurikulum-profile', compact(
            'userId', 'academicYears', 'academicYear', 'academicYearId',
            'profiles', 'schools', 'settings', 'counts'
        ));
    }

    public function store(Request $request, string $userId)
    {
        abort_unless($this->access->canViewAllSchools($request->user()), 403, 'Hanya Departemen/Super Admin yang dapat memetakan lintas sekolah.');

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'assignments' => 'required|array',
            'assignments.*.curriculum_profile_id' => ['nullable', Rule::exists('curriculum_profiles', 'id')],
            'assignments.*.kokurikuler_type' => 'nullable|string|max:30',
        ]);

        $validSchoolIds = School::whereIn('id', array_keys($validated['assignments']))->pluck('id')->all();
        $saved = 0;

        foreach ($validated['assignments'] as $schoolId => $assignment) {
            if (! in_array($schoolId, $validSchoolIds, true) || empty($assignment['curriculum_profile_id'])) {
                continue;
            }

            $existing = SchoolCurriculumSetting::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $validated['academic_year_id'])
                ->first();

            if ($existing?->is_locked && (string) $existing->curriculum_profile_id !== (string) $assignment['curriculum_profile_id']) {
                continue;
            }

            $setting = SchoolCurriculumSetting::updateOrCreate(
                ['school_id' => $schoolId, 'academic_year_id' => $validated['academic_year_id']],
                [
                    'curriculum_profile_id' => $assignment['curriculum_profile_id'],
                    'kokurikuler_type' => $assignment['kokurikuler_type'] ?? null,
                    'updated_by' => $request->user()->id,
                ]
            );

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'CURRICULUM_PROFILE_BULK_UPDATED',
                'table_name' => $setting->getTable(),
                'record_id' => $setting->id,
                'record_type' => $setting::class,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]);
            $saved++;
        }

        return redirect()
            ->route('user.schools-global.kurikulum-profile.index', ['userId' => $userId, 'academic_year_id' => $validated['academic_year_id']])
            ->with('success', $saved.' sekolah berhasil dipetakan ke profil kurikulum.');
    }

    private function documentCounts(?string $academicYearId): array
    {
        if (! $academicYearId) {
            return [];
        }

        $classes = [
            'cp' => CapaianPembelajaran::class,
            'tp' => TujuanPembelajaran::class,
            'atp' => AlurTujuanPembelajaran::class,
            'prota' => Prota::class,
            'prosem' => Prosem::class,
            'perangkat' => PerangkatPembelajaran::class,
        ];

        $counts = [];
        foreach ($classes as $key => $class) {
            $table = (new $class)->getTable();
            $counts[$key] = DB::table($table)
                ->where('academic_year_id', $academicYearId)
                ->select('school_id', DB::raw('count(*) as total'))
                ->groupBy('school_id')
                ->pluck('total', 'school_id')
                ->all();
        }

        return $counts;
    }
}
