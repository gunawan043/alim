<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\TahfidzLessonPlan;
use App\Models\TahfidzMuqorrorPlan;
use App\Models\TahfidzPackage;
use App\Services\TahfidzAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TahfidzMasterController extends Controller
{
    private const MONTHS = [
        1 => 'Juli', 2 => 'Agustus', 3 => 'September', 4 => 'Oktober',
        5 => 'November', 6 => 'Desember', 7 => 'Januari', 8 => 'Februari',
        9 => 'Maret', 10 => 'April', 11 => 'Mei', 12 => 'Juni',
    ];

    public function __construct(private readonly TahfidzAccess $access) {}

    public function muqorrorIndex(Request $request, string $userId)
    {
        $this->authorizeMuqorrorView($request);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeYear?->id);
        $semester = $request->input('semester', 'ganjil');
        $schoolId = $this->schoolId($request);

        $plans = TahfidzMuqorrorPlan::with(['package:id,name', 'gradeLevel:id,name', 'academicYear:id,name', 'school:id,name'])
            ->withCount('months')
            ->when(! $this->access->canViewAllSchools($request->user()), function ($q) use ($schoolId) {
                $q->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $schoolId));
            })
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($semester, fn ($q) => $q->where('semester', $semester))
            ->when($request->filled('jenjang'), fn ($q) => $q->where('jenjang', $request->jenjang))
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total' => $plans->count(),
            'active' => $plans->where('is_active', true)->count(),
            'months' => $plans->sum('months_count'),
            'schools' => $plans->pluck('school_id')->filter()->unique()->count(),
        ];
        $canManage = $this->access->canManageCurriculum($request->user());

        return view('tahfidz.muqorror.index', compact(
            'userId', 'plans', 'academicYears', 'academicYearId', 'semester', 'stats', 'canManage'
        ));
    }

    public function muqorrorCreate(Request $request, string $userId)
    {
        $this->authorizeMuqorrorManage($request);

        return view('tahfidz.muqorror.form', $this->muqorrorFormData($request));
    }

    public function muqorrorStore(Request $request, string $userId)
    {
        $this->authorizeMuqorrorManage($request);

        $validated = $this->validateMuqorror($request);
        $plan = DB::transaction(function () use ($validated, $request) {
            $plan = TahfidzMuqorrorPlan::create($this->muqorrorHeader($validated, $request));
            $this->syncMonths($plan, $validated['months'] ?? []);

            return $plan;
        });

        return redirect()
            ->route('user.tahfidz.muqorror.show', ['userId' => $userId, 'id' => $plan->id])
            ->with('success', 'Muqorror tahfizh berhasil disusun.');
    }

    public function muqorrorShow(Request $request, string $userId, string $id)
    {
        $plan = TahfidzMuqorrorPlan::with(['months', 'package', 'gradeLevel', 'academicYear', 'school', 'creator'])->findOrFail($id);
        $this->authorizeMuqorrorRecord($request, $plan);

        return view('tahfidz.muqorror.show', compact('userId', 'plan'));
    }

    public function muqorrorEdit(Request $request, string $userId, string $id)
    {
        $this->authorizeMuqorrorManage($request);
        $plan = TahfidzMuqorrorPlan::with('months')->findOrFail($id);

        return view('tahfidz.muqorror.form', $this->muqorrorFormData($request, $plan));
    }

    public function muqorrorUpdate(Request $request, string $userId, string $id)
    {
        $this->authorizeMuqorrorManage($request);
        $plan = TahfidzMuqorrorPlan::findOrFail($id);
        $validated = $this->validateMuqorror($request);

        DB::transaction(function () use ($plan, $validated, $request) {
            $plan->update($this->muqorrorHeader($validated, $request));
            $plan->months()->delete();
            $this->syncMonths($plan, $validated['months'] ?? []);
        });

        return redirect()
            ->route('user.tahfidz.muqorror.show', ['userId' => $userId, 'id' => $plan->id])
            ->with('success', 'Muqorror tahfizh diperbarui.');
    }

    public function muqorrorDestroy(Request $request, string $userId, string $id)
    {
        $this->authorizeMuqorrorManage($request);
        $plan = TahfidzMuqorrorPlan::findOrFail($id);
        $plan->months()->delete();
        $plan->delete();

        return redirect()
            ->route('user.tahfidz.muqorror.index', ['userId' => $userId])
            ->with('success', 'Muqorror tahfizh dihapus.');
    }

    public function muqorrorPrint(Request $request, string $userId, string $id)
    {
        $plan = TahfidzMuqorrorPlan::with(['months', 'package', 'gradeLevel', 'academicYear', 'school', 'creator'])->findOrFail($id);
        $this->authorizeMuqorrorRecord($request, $plan);

        return app(\App\Services\Pdf\DocumentPdfService::class)->fromView(
            'tahfidz.muqorror.print',
            compact('plan'),
            'Muqorror-'.$plan->title,
            'landscape',
        );
    }

    public function rppIndex(Request $request, string $userId)
    {
        $this->authorizeRppView($request);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeYear?->id);
        $schoolId = $this->schoolId($request);

        $plans = TahfidzLessonPlan::with(['school:id,name', 'gradeLevel:id,name', 'academicYear:id,name'])
            ->withCount('items')
            ->when(! $this->access->canViewAllSchools($request->user()), function ($q) use ($schoolId) {
                $q->where(fn ($scope) => $scope->whereNull('school_id')->orWhere('school_id', $schoolId));
            })
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total' => $plans->count(),
            'tahfizh' => $plans->where('type', 'tahfizh')->count(),
            'iqro' => $plans->where('type', 'iqro')->count(),
            'items' => $plans->sum('items_count'),
        ];
        $canManage = $this->access->canManageCurriculum($request->user())
            || $this->access->isSchoolCoordinator($request->user(), $schoolId);

        return view('tahfidz.rpp.index', compact('userId', 'plans', 'academicYears', 'academicYearId', 'stats', 'canManage'));
    }

    public function rppCreate(Request $request, string $userId)
    {
        $this->authorizeRppManage($request);

        return view('tahfidz.rpp.form', $this->rppFormData($request));
    }

    public function rppStore(Request $request, string $userId)
    {
        $this->authorizeRppManage($request);
        $validated = $this->validateRpp($request);

        $plan = DB::transaction(function () use ($validated, $request) {
            $plan = TahfidzLessonPlan::create($this->rppHeader($validated, $request));
            $this->syncItems($plan, $validated['items'] ?? []);

            return $plan;
        });

        return redirect()
            ->route('user.tahfidz.rpp.show', ['userId' => $userId, 'id' => $plan->id])
            ->with('success', 'RPP tahfizh berhasil disusun.');
    }

    public function rppShow(Request $request, string $userId, string $id)
    {
        $plan = TahfidzLessonPlan::with(['items', 'school', 'gradeLevel', 'academicYear', 'creator'])->findOrFail($id);
        $this->authorizeRppRecord($request, $plan);

        return view('tahfidz.rpp.show', compact('userId', 'plan'));
    }

    public function rppEdit(Request $request, string $userId, string $id)
    {
        $this->authorizeRppManage($request);
        $plan = TahfidzLessonPlan::with('items')->findOrFail($id);

        return view('tahfidz.rpp.form', $this->rppFormData($request, $plan));
    }

    public function rppUpdate(Request $request, string $userId, string $id)
    {
        $this->authorizeRppManage($request);
        $plan = TahfidzLessonPlan::findOrFail($id);
        $validated = $this->validateRpp($request);

        DB::transaction(function () use ($plan, $validated, $request) {
            $plan->update($this->rppHeader($validated, $request));
            $plan->items()->delete();
            $this->syncItems($plan, $validated['items'] ?? []);
        });

        return redirect()
            ->route('user.tahfidz.rpp.show', ['userId' => $userId, 'id' => $plan->id])
            ->with('success', 'RPP tahfizh diperbarui.');
    }

    public function rppDestroy(Request $request, string $userId, string $id)
    {
        $this->authorizeRppManage($request);
        $plan = TahfidzLessonPlan::findOrFail($id);
        $plan->items()->delete();
        $plan->delete();

        return redirect()
            ->route('user.tahfidz.rpp.index', ['userId' => $userId])
            ->with('success', 'RPP tahfizh dihapus.');
    }

    public function rppPrint(Request $request, string $userId, string $id)
    {
        $plan = TahfidzLessonPlan::with(['items', 'school', 'gradeLevel', 'academicYear', 'creator'])->findOrFail($id);
        $this->authorizeRppRecord($request, $plan);

        return app(\App\Services\Pdf\DocumentPdfService::class)->fromView(
            'tahfidz.rpp.print',
            compact('plan'),
            'RPP-Tahfidz-'.$plan->title,
        );
    }

    private function muqorrorFormData(Request $request, ?TahfidzMuqorrorPlan $plan = null): array
    {
        $schoolId = $this->schoolId($request);
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $gradeLevels = GradeLevel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('level')->get();
        $packages = TahfidzPackage::query()->where('is_active', true)->orderBy('name')->get();
        $schools = $this->access->canViewAllSchools($request->user()) ? School::orderBy('name')->get(['id', 'name']) : collect();

        return [
            'userId' => $request->route('userId'),
            'plan' => $plan,
            'months' => self::MONTHS,
            'monthRows' => old('months', $plan?->months?->keyBy('month')->toArray() ?? []),
            'academicYears' => $academicYears,
            'gradeLevels' => $gradeLevels,
            'packages' => $packages,
            'schools' => $schools,
        ];
    }

    private function rppFormData(Request $request, ?TahfidzLessonPlan $plan = null): array
    {
        $schoolId = $this->schoolId($request);
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $gradeLevels = GradeLevel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('level')->get();

        return [
            'userId' => $request->route('userId'),
            'plan' => $plan,
            'academicYears' => $academicYears,
            'gradeLevels' => $gradeLevels,
            'items' => old('items', $plan?->items?->toArray() ?? []),
            'schools' => $this->access->canViewAllSchools($request->user()) ? School::orderBy('name')->get(['id', 'name']) : collect(),
        ];
    }

    private function validateMuqorror(Request $request): array
    {
        return $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'jenjang' => 'nullable|in:sd,smp,sma',
            'tahfidz_package_id' => 'nullable|exists:tahfidz_packages,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'school_id' => 'nullable|exists:schools,id',
            'title' => 'required|string|max:150',
            'notes' => 'nullable|string|max:2000',
            'months' => 'array',
            'months.*.month' => 'required|integer|between:1,12',
            'months.*.target_juz_start' => 'nullable|integer|between:1,30',
            'months.*.target_juz_end' => 'nullable|integer|between:1,30',
            'months.*.target_pages' => 'nullable|numeric|min:0|max:604',
            'months.*.target_lines_month' => 'nullable|integer|min:0|max:10000',
            'months.*.target_lines_day' => 'nullable|numeric|min:0|max:1000',
            'months.*.active_days' => 'nullable|integer|min:0|max:31',
            'months.*.from_surah_name' => 'nullable|string|max:100',
            'months.*.from_ayat' => 'nullable|integer|min:0',
            'months.*.to_surah_name' => 'nullable|string|max:100',
            'months.*.to_ayat' => 'nullable|integer|min:0',
            'months.*.exam_type' => 'nullable|in:sts,sas',
            'months.*.target_semester' => 'nullable|string|max:100',
            'months.*.target_mid_semester' => 'nullable|string|max:100',
            'months.*.ziyadah_meetings' => 'nullable|integer|min:0|max:31',
            'months.*.rabth_meetings' => 'nullable|integer|min:0|max:31',
            'months.*.meeting_no' => 'nullable|integer|min:0|max:31',
            'months.*.alumni_material' => 'nullable|string|max:2000',
            'months.*.non_alumni_material' => 'nullable|string|max:2000',
            'months.*.notes' => 'nullable|string|max:1000',
        ]);
    }

    private function validateRpp(Request $request): array
    {
        return $request->validate([
            'type' => 'required|in:tahfizh,iqro',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'school_id' => 'nullable|exists:schools,id',
            'title' => 'required|string|max:150',
            'kbm_month' => 'nullable|string|max:30',
            'monthly_target' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'items' => 'array',
            'items.*.week' => 'nullable|integer|min:1|max:5',
            'items.*.section' => 'nullable|string|max:30',
            'items.*.learning_day' => 'nullable|string|max:30',
            'items.*.week_target' => 'nullable|string|max:255',
            'items.*.daily_target' => 'nullable|string|max:2000',
            'items.*.teacher_activity' => 'nullable|string|max:3000',
            'items.*.student_activity' => 'nullable|string|max:3000',
            'items.*.duration_minutes' => 'nullable|integer|min:0|max:300',
            'items.*.notes' => 'nullable|string|max:1000',
        ]);
    }

    private function muqorrorHeader(array $validated, Request $request): array
    {
        $schoolId = $validated['school_id'] ?? $this->schoolId($request);
        $workUnitId = $schoolId
            ? School::whereKey($schoolId)->value('work_unit_id')
            : ($this->access->departmentWorkUnit()?->id ?? $this->schoolId($request));

        return [
            'work_unit_id' => $workUnitId,
            'school_id' => $schoolId,
            'tahfidz_package_id' => $validated['tahfidz_package_id'] ?? null,
            'grade_level_id' => $validated['grade_level_id'] ?? null,
            'academic_year_id' => $validated['academic_year_id'],
            'semester' => $validated['semester'],
            'jenjang' => $validated['jenjang'] ?? null,
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
            'is_active' => true,
            'created_by' => $request->user()?->id,
        ];
    }

    private function rppHeader(array $validated, Request $request): array
    {
        $schoolId = $validated['school_id'] ?? $this->schoolId($request);
        $workUnitId = $schoolId
            ? School::whereKey($schoolId)->value('work_unit_id')
            : ($this->access->departmentWorkUnit()?->id ?? $this->schoolId($request));

        return [
            'work_unit_id' => $workUnitId,
            'school_id' => $schoolId,
            'grade_level_id' => $validated['grade_level_id'] ?? null,
            'academic_year_id' => $validated['academic_year_id'],
            'semester' => $validated['semester'],
            'type' => $validated['type'],
            'title' => $validated['title'],
            'kbm_month' => $validated['kbm_month'] ?? null,
            'monthly_target' => $validated['monthly_target'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
            'created_by' => $request->user()?->id,
        ];
    }

    private function syncMonths(TahfidzMuqorrorPlan $plan, array $months): void
    {
        foreach ($months as $index => $month) {
            $plan->months()->create([
                'month' => $month['month'] ?? ((int) $index + 1),
                'target_juz_start' => $month['target_juz_start'] ?? null,
                'target_juz_end' => $month['target_juz_end'] ?? null,
                'target_pages' => $month['target_pages'] ?? null,
                'target_lines_month' => $month['target_lines_month'] ?? null,
                'target_lines_day' => $month['target_lines_day'] ?? null,
                'active_days' => $month['active_days'] ?? null,
                'from_surah_name' => $month['from_surah_name'] ?? null,
                'from_ayat' => $month['from_ayat'] ?? null,
                'to_surah_name' => $month['to_surah_name'] ?? null,
                'to_ayat' => $month['to_ayat'] ?? null,
                'exam_type' => $month['exam_type'] ?? null,
                'target_semester' => $month['target_semester'] ?? null,
                'target_mid_semester' => $month['target_mid_semester'] ?? null,
                'ziyadah_meetings' => $month['ziyadah_meetings'] ?? null,
                'rabth_meetings' => $month['rabth_meetings'] ?? null,
                'meeting_no' => $month['meeting_no'] ?? null,
                'alumni_material' => $month['alumni_material'] ?? null,
                'non_alumni_material' => $month['non_alumni_material'] ?? null,
                'notes' => $month['notes'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    private function syncItems(TahfidzLessonPlan $plan, array $items): void
    {
        foreach ($items as $index => $item) {
            if (collect($item)->filter(fn ($value) => filled($value))->isEmpty()) {
                continue;
            }

            $plan->items()->create([
                'week' => $item['week'] ?? null,
                'section' => $item['section'] ?? null,
                'learning_day' => $item['learning_day'] ?? null,
                'week_target' => $item['week_target'] ?? null,
                'daily_target' => $item['daily_target'] ?? null,
                'teacher_activity' => $item['teacher_activity'] ?? null,
                'student_activity' => $item['student_activity'] ?? null,
                'duration_minutes' => $item['duration_minutes'] ?? null,
                'notes' => $item['notes'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    private function authorizeMuqorrorView(Request $request): void
    {
        abort_unless(
            $this->access->canViewAllSchools($request->user())
            || $this->access->isSchoolCoordinator($request->user(), $this->schoolId($request))
            || $this->access->isSchoolLeader($request->user())
            || $this->access->isTahfidzTeacher($request->user())
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang melihat master muqorror tahfizh.'
        );
    }

    private function authorizeMuqorrorManage(Request $request): void
    {
        abort_unless($this->access->canManageCurriculum($request->user()), 403, 'Hanya Departemen Tahfidz yang dapat menyusun muqorror master.');
    }

    private function authorizeMuqorrorRecord(Request $request, TahfidzMuqorrorPlan $plan): void
    {
        if ($this->access->canViewAllSchools($request->user())) {
            return;
        }

        abort_unless(
            $plan->school_id === null || (string) $plan->school_id === (string) $this->schoolId($request),
            403,
            'Anda tidak berwenang melihat muqorror ini.'
        );
    }

    private function authorizeRppView(Request $request): void
    {
        abort_unless(
            $this->access->canViewAllSchools($request->user())
            || $this->access->isSchoolCoordinator($request->user(), $this->schoolId($request))
            || $this->access->isSchoolLeader($request->user())
            || $this->access->isTahfidzTeacher($request->user())
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang melihat RPP tahfizh.'
        );
    }

    private function authorizeRppManage(Request $request): void
    {
        abort_unless(
            $this->access->canManageCurriculum($request->user())
            || $this->access->isSchoolCoordinator($request->user(), $this->schoolId($request)),
            403,
            'Anda tidak berwenang menyusun RPP tahfizh.'
        );
    }

    private function authorizeRppRecord(Request $request, TahfidzLessonPlan $plan): void
    {
        if ($this->access->canViewAllSchools($request->user())) {
            return;
        }

        abort_unless(
            $plan->school_id === null
            || (string) $plan->school_id === (string) $this->schoolId($request),
            403,
            'Anda tidak berwenang melihat RPP ini.'
        );
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId')
            ?: $request->user()?->employment?->school_id;
    }
}
