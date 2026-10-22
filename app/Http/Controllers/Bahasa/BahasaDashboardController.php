<?php

namespace App\Http\Controllers\Bahasa;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\LanguageMonthlyActivity;
use App\Models\LanguageMufradatSetoran;
use App\Models\LanguageViolation;
use App\Models\LanguageVocabulary;
use App\Models\LanguageVocabularyPeriod;
use App\Services\BahasaAccess;
use Illuminate\Http\Request;

class BahasaDashboardController extends Controller
{
    public function __construct(private readonly BahasaAccess $access) {}

    public function index(Request $request, string $userId)
    {
        abort_unless($this->access->canView($request->user()), 403);

        $schoolId = $request->attributes->get('schoolContextId');
        $activeYear = AcademicYear::query()->where('is_active', true)->first();

        $periods = LanguageVocabularyPeriod::query()
            ->when($schoolId, fn ($query) => $query->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $schoolId)))
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
            ->orderByDesc('period_start')
            ->get();

        $activePeriod = $periods->firstWhere('is_active', true) ?? $periods->first();

        $stats = [
            'periods' => $periods->count(),
            'vocabularies' => $activePeriod ? LanguageVocabulary::where('period_id', $activePeriod->id)->count() : 0,
            'setoran_month' => LanguageMufradatSetoran::query()
                ->when($schoolId, fn ($query) => $query->whereHas('period', fn ($p) => $p->where('school_id', $schoolId)))
                ->whereYear('setoran_date', now()->year)
                ->whereMonth('setoran_date', now()->month)
                ->count(),
            'violations_month' => LanguageViolation::query()
                ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
                ->whereYear('violation_date', now()->year)
                ->whereMonth('violation_date', now()->month)
                ->count(),
            'points_month' => (int) LanguageViolation::query()
                ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
                ->whereYear('violation_date', now()->year)
                ->whereMonth('violation_date', now()->month)
                ->sum('points'),
            'activities_month' => LanguageMonthlyActivity::query()
                ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
                ->whereYear('period_month', now()->year)
                ->whereMonth('period_month', now()->month)
                ->count(),
        ];

        $recentViolations = LanguageViolation::with(['student:id,name', 'reporter:id,name'])
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->orderByDesc('violation_date')
            ->limit(6)
            ->get();

        $recentActivities = LanguageMonthlyActivity::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->orderByDesc('activity_date')
            ->limit(5)
            ->get();

        return view('bahasa.dashboard', compact(
            'userId', 'activeYear', 'activePeriod', 'periods', 'stats', 'recentViolations', 'recentActivities'
        ));
    }
}
