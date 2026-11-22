<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesReportScope;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Services\Academic\CurriculumConsistencyService;
use Illuminate\Http\Request;

/**
 * Dashboard mutu pendidikan: keterhubungan kurikulum, konsistensi JP,
 * kesiapan rapor, dan beban guru.
 */
class AcademicQualityController extends Controller
{
    use ResolvesReportScope;

    public function index(Request $request, string $userId, CurriculumConsistencyService $service)
    {
        $scope = $this->reportScope($request);

        $schools = $scope->isSchool()
            ? School::whereKey($scope->schoolId)->get(['id', 'name'])
            : ($scope->isGlobal()
                ? School::where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect());

        $selectedSchoolId = $scope->isSchool()
            ? $scope->schoolId
            : ($scope->isGlobal() ? $request->input('school_id') : null);

        // Pemegang akses global boleh menyaring satu unit; chip ikut menyebut
        // unit itu supaya tidak terbaca "seluruh pondok" padahal sudah disaring.
        if ($scope->isGlobal() && $selectedSchoolId) {
            $selected = $schools->firstWhere('id', $selectedSchoolId);
            abort_unless($selected, 403, 'Unit tidak dapat diakses.');
            $scope = $scope->withLabel($selected->name);
        }

        $schoolIds = $selectedSchoolId
            ? [$selectedSchoolId]
            : $schools->pluck('id')->all();

        $years = AcademicYear::orderByDesc('is_active')->orderByDesc('start_date')->get()->unique('name')->values();
        $activeYear = $years->firstWhere('is_active', true) ?? $years->first();
        $yearId = $request->input('academic_year_id') ?: $activeYear?->id;
        $year = $yearId ? AcademicYear::find($yearId) : null;
        $semester = $request->input('semester') ?: ($year?->semester ?? 'ganjil');

        $grades = GradeLevel::whereIn('school_id', $schoolIds)
            ->where('is_active', true)
            ->orderBy('level')
            ->get();

        $report = $service->build(
            $schoolIds,
            $yearId,
            $semester,
            $request->input('grade_level_id') ?: null,
        );

        return view('user.laporan.pendidikan-mutu', compact(
            'userId', 'schools', 'years', 'year', 'semester', 'grades', 'report', 'scope'
        ));
    }
}
