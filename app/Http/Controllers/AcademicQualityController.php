<?php

namespace App\Http\Controllers;

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
    public function index(Request $request, string $userId, CurriculumConsistencyService $service)
    {
        $schoolId = $request->attributes->get('schoolContextId')
            ?: $request->user()?->employment?->school_id;

        $schools = $schoolId
            ? School::whereKey($schoolId)->get(['id', 'name'])
            : School::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $selectedSchoolId = $schoolId ?: $request->input('school_id');
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
            'userId', 'schools', 'years', 'year', 'semester', 'grades', 'report', 'schoolId'
        ));
    }
}
