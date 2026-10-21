<?php

namespace App\Http\Controllers\Osipp;

use App\Models\Osipp\OsippActivity;
use App\Models\Osipp\OsippCoaching;
use App\Models\Osipp\OsippDocument;
use App\Models\Osipp\OsippEvaluation;
use App\Models\Osipp\OsippMember;
use App\Models\Osipp\OsippProgram;
use Illuminate\Http\Request;

class OsippDashboardController extends OsippBaseController
{
    public function index(Request $request)
    {
        $context = $this->context($request);
        $period = $context['period'];

        $stats = $period ? $this->workspace->statsForPeriod($period) : [
            'members' => 0,
            'core_members' => 0,
            'programs' => 0,
            'programs_running' => 0,
            'programs_completed' => 0,
            'activities' => 0,
            'activities_pending' => 0,
            'activities_completed' => 0,
            'documents' => 0,
            'documents_pending' => 0,
            'coachings' => 0,
            'evaluation_score' => 0,
            'evaluation_grade' => null,
        ];

        $upcomingActivities = collect();
        $pendingActivities = collect();
        $pendingDocuments = collect();
        $programs = collect();
        $recentCoachings = collect();
        $latestEvaluation = null;
        $coreMembers = collect();

        if ($period) {
            $upcomingActivities = OsippActivity::query()
                ->with(['program:id,name', 'responsible.student:id,name'])
                ->where('period_id', $period->id)
                ->whereDate('activity_date', '>=', today())
                ->whereNotIn('status', [OsippActivity::STATUS_REJECTED])
                ->orderBy('activity_date')
                ->limit(6)
                ->get();

            $pendingActivities = OsippActivity::query()
                ->with(['program:id,name', 'responsible.student:id,name'])
                ->where('period_id', $period->id)
                ->where('status', OsippActivity::STATUS_SUBMITTED)
                ->latest('submitted_at')
                ->limit(5)
                ->get();

            $pendingDocuments = OsippDocument::query()
                ->with(['program:id,name', 'activity:id,title'])
                ->where('period_id', $period->id)
                ->where('status', OsippDocument::STATUS_SUBMITTED)
                ->latest('submitted_at')
                ->limit(5)
                ->get();

            $programs = OsippProgram::query()
                ->with('responsible.student:id,name')
                ->withCount('activities')
                ->where('period_id', $period->id)
                ->orderByDesc('progress')
                ->orderBy('name')
                ->limit(6)
                ->get();

            $recentCoachings = OsippCoaching::query()
                ->with(['mentor:id,name', 'member.student:id,name'])
                ->withCount('attendances')
                ->where('period_id', $period->id)
                ->orderByDesc('coaching_date')
                ->limit(5)
                ->get();

            $latestEvaluation = OsippEvaluation::query()
                ->with('evaluator:id,name')
                ->where('period_id', $period->id)
                ->orderByRaw("CASE WHEN status = 'final' THEN 0 ELSE 1 END")
                ->orderByDesc('evaluation_date')
                ->first();

            $coreMembers = OsippMember::query()
                ->with('student:id,name,nis,nisn,gender')
                ->where('period_id', $period->id)
                ->where('status', OsippMember::STATUS_ACTIVE)
                ->where('member_role', OsippMember::ROLE_PENGURUS)
                ->orderBy('sort_order')
                ->orderBy('position')
                ->limit(8)
                ->get();
        }

        return view('osipp.dashboard', array_merge($context, compact(
            'period',
            'stats',
            'upcomingActivities',
            'pendingActivities',
            'pendingDocuments',
            'programs',
            'recentCoachings',
            'latestEvaluation',
            'coreMembers',
        )));
    }
}
