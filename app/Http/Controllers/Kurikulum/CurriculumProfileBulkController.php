<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\CurriculumProfile;
use App\Models\CurriculumProfileMappingLog;
use App\Models\PerangkatPembelajaran;
use App\Models\Prosem;
use App\Models\Prota;
use App\Models\School;
use App\Models\SchoolCurriculumSetting;
use App\Models\TujuanPembelajaran;
use App\Services\TahfidzAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        $documentTotals = collect($counts)->map(fn ($rows) => array_sum($rows))->all();
        $profileDistribution = $profiles->mapWithKeys(fn ($profile) => [
            $profile->id => $settings->where('curriculum_profile_id', $profile->id)->count(),
        ])->all();
        $unmappedCount = $schools->count() - $settings->whereNotNull('curriculum_profile_id')->count();

        $mappingLogs = CurriculumProfileMappingLog::with('changer:id,name')
            ->where('academic_year_id', $academicYearId)
            ->latest()
            ->limit(200)
            ->get();
        $batches = $mappingLogs
            ->groupBy('batch_id')
            ->map(function ($logs, $batchId) {
                return (object) [
                    'batch_id' => $batchId,
                    'schools_count' => $logs->count(),
                    'changed_at' => $logs->max('created_at'),
                    'changer' => $logs->first()?->changer?->name,
                    'rolled_back' => $logs->whereNotNull('rolled_back_at')->count(),
                    'pending' => $logs->whereNull('rolled_back_at')->count(),
                ];
            })
            ->sortByDesc('changed_at')
            ->take(10)
            ->values();

        return view('schools-global.kurikulum-profile', compact(
            'userId', 'academicYears', 'academicYear', 'academicYearId',
            'profiles', 'schools', 'settings', 'counts', 'documentTotals',
            'profileDistribution', 'unmappedCount', 'batches'
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
        $batchId = (string) Str::uuid();
        $saved = 0;

        DB::transaction(function () use ($validated, $validSchoolIds, $batchId, $request, &$saved) {
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

                $oldProfileId = $existing?->curriculum_profile_id;
                $oldKokurikuler = $existing?->kokurikuler_type;
                $newKokurikuler = $assignment['kokurikuler_type'] ?? null;

                if ((string) $oldProfileId === (string) $assignment['curriculum_profile_id']
                    && (string) $oldKokurikuler === (string) $newKokurikuler) {
                    continue;
                }

                $setting = SchoolCurriculumSetting::updateOrCreate(
                    ['school_id' => $schoolId, 'academic_year_id' => $validated['academic_year_id']],
                    [
                        'curriculum_profile_id' => $assignment['curriculum_profile_id'],
                        'kokurikuler_type' => $newKokurikuler,
                        'updated_by' => $request->user()->id,
                    ]
                );

                CurriculumProfileMappingLog::create([
                    'batch_id' => $batchId,
                    'school_id' => $schoolId,
                    'academic_year_id' => $validated['academic_year_id'],
                    'old_profile_id' => $oldProfileId,
                    'new_profile_id' => $assignment['curriculum_profile_id'],
                    'old_kokurikuler_type' => $oldKokurikuler,
                    'new_kokurikuler_type' => $newKokurikuler,
                    'old_setting_exists' => $existing !== null,
                    'changed_by' => $request->user()->id,
                ]);

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
        });

        return redirect()
            ->route('user.schools-global.kurikulum-profile.index', ['userId' => $userId, 'academic_year_id' => $validated['academic_year_id']])
            ->with('success', $saved.' sekolah berhasil dipetakan ke profil kurikulum.');
    }

    public function rollback(Request $request, string $userId, string $batchId)
    {
        abort_unless($this->access->canViewAllSchools($request->user()), 403, 'Hanya Departemen/Super Admin yang dapat membatalkan pemetaan.');

        $logs = CurriculumProfileMappingLog::query()
            ->where('batch_id', $batchId)
            ->whereNull('rolled_back_at')
            ->get();

        if ($logs->isEmpty()) {
            return back()->with('error', 'Batch pemetaan ini sudah dibatalkan atau tidak ditemukan.');
        }

        $restored = 0;
        $skipped = 0;

        DB::transaction(function () use ($logs, $request, &$restored, &$skipped) {
            foreach ($logs as $log) {
                $setting = SchoolCurriculumSetting::query()
                    ->where('school_id', $log->school_id)
                    ->where('academic_year_id', $log->academic_year_id)
                    ->first();

                if ($setting?->is_locked) {
                    $skipped++;
                    continue;
                }

                if ($log->old_setting_exists && $log->old_profile_id) {
                    SchoolCurriculumSetting::updateOrCreate(
                        ['school_id' => $log->school_id, 'academic_year_id' => $log->academic_year_id],
                        [
                            'curriculum_profile_id' => $log->old_profile_id,
                            'kokurikuler_type' => $log->old_kokurikuler_type,
                            'updated_by' => $request->user()->id,
                        ]
                    );
                } else {
                    SchoolCurriculumSetting::query()
                        ->where('school_id', $log->school_id)
                        ->where('academic_year_id', $log->academic_year_id)
                        ->delete();
                }

                $log->forceFill([
                    'rolled_back_at' => now(),
                    'rolled_back_by' => $request->user()->id,
                ])->save();

                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'CURRICULUM_PROFILE_BULK_ROLLED_BACK',
                    'table_name' => 'school_curriculum_settings',
                    'record_id' => $log->school_id,
                    'record_type' => SchoolCurriculumSetting::class,
                    'ip_address' => $request->ip(),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                ]);
                $restored++;
            }
        });

        $message = $restored.' sekolah dikembalikan ke profil sebelumnya.';
        if ($skipped > 0) {
            $message .= ' '.$skipped.' sekolah dilewati karena terkunci.';
        }

        return back()->with('success', $message);
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
