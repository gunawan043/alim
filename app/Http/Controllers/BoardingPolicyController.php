<?php

namespace App\Http\Controllers;

use App\Models\BoardingPolicy;
use App\Models\Dormitory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BoardingPolicyController extends Controller
{
    public function index()
    {
        $policies = BoardingPolicy::with('assignments.dormitory')
            ->orderBy('name')
            ->paginate(20);

        $stats = [
            'total' => BoardingPolicy::count(),
            'active' => BoardingPolicy::where('is_active', true)->count(),
            'quota' => BoardingPolicy::where('leave_strategy', 'quota')->count(),
            'unrestricted' => BoardingPolicy::where('leave_strategy', 'unrestricted')->count(),
            'banned' => BoardingPolicy::where('leave_strategy', 'banned')->count(),
        ];

        return view('dormitory.policies.index', compact('policies', 'stats'));
    }

    public function create(Request $request)
    {
        $dormitories = Dormitory::where('is_active', true)->orderBy('name')->get();
        $userId = $request->route('userId');

        return view('dormitory.policies.create', compact('dormitories', 'userId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:64|unique:boarding_policies,code',
            'description' => 'nullable|string',
            'leave_strategy' => 'required|in:quota,unrestricted,banned',
            'leave_quota' => 'nullable|integer|min:0|required_if:leave_strategy,quota',
            'leave_quota_period' => 'required_if:leave_strategy,quota|in:weekly,monthly,semester,yearly',
            'visit_strategy' => 'required|in:quota,unrestricted,banned',
            'visit_quota' => 'nullable|integer|min:0|required_if:visit_strategy,quota',
            'visit_quota_period' => 'required_if:visit_strategy,quota|in:daily,weekly,monthly',
            'max_visitors_per_visit' => 'nullable|integer|min:1',
            'curfew_hour' => 'nullable|integer|min:0|max:23',
            'special_permission_allowed' => 'boolean',
            'special_permission_types' => 'nullable|array',
            'special_permission_types.*' => 'in:medical,emergency,family,competition,school_activity',
            'auto_sync_academic_attendance' => 'boolean',
            'is_active' => 'boolean',
            'dormitory_ids' => 'sometimes|array',
            'dormitory_ids.*' => 'exists:dormitories,id',
        ]);

        $policy = BoardingPolicy::create(array_filter($validated, fn ($v, $k) => in_array($k, [
            'name', 'code', 'description', 'leave_strategy', 'leave_quota', 'leave_quota_period',
            'visit_strategy', 'visit_quota', 'visit_quota_period', 'max_visitors_per_visit',
            'curfew_hour', 'special_permission_allowed', 'special_permission_types',
            'auto_sync_academic_attendance', 'is_active',
        ], true)));

        // Assign to dormitories if selected
        if ($request->filled('dormitory_ids')) {
            foreach ($request->dormitory_ids as $dormId) {
                $policy->assignments()->create([
                    'policy_assignment_type' => 'dormitory',
                    'target_id' => $dormId,
                    'effective_from' => now(),
                    'priority' => 0,
                ]);
            }
        }

        return redirect()->route('user.boarding-policies.index')
            ->with('success', 'Kebijakan asrama berhasil dibuat.');
    }

    public function updateQuota(Request $request, string $id)
    {
        $policy = BoardingPolicy::findOrFail($id);

        $validated = $request->validate([
            'leave_quota' => 'nullable|integer|min:0',
            'leave_quota_period' => 'nullable|in:weekly,monthly,semester,yearly',
            'visit_quota' => 'nullable|integer|min:0',
            'visit_quota_period' => 'nullable|in:daily,weekly,monthly',
            'max_visitors_per_visit' => 'nullable|integer|min:1',
            'curfew_hour' => 'nullable|integer|min:0|max:23',
        ]);

        $policy->update($validated);

        return back()->with('success', 'Kuota berhasil diperbarui.');
    }

    public function show(Request $request, string $id)
    {
        $policy = BoardingPolicy::with([
            'assignments.dormitory',
            'assignments' => fn ($q) => $q->orderByDesc('created_at')->take(10),
        ])->findOrFail($id);

        // Gather quota usage for a sample student from assigned dormitories (for demonstration)
        $sampleStudent = null;
        $visitUsage = null;
        $leaveUsage = null;
        $remainingVisit = null;
        $remainingLeave = null;
        $sampleStudentId = null;
        $sampleDormId = null;

        if ($policy->assignments->count() > 0) {
            // Pick first dorm assignment
            $firstAssign = $policy->assignments->first();
            $dorm = $firstAssign->dormitory;
            if ($dorm) {
                $sampleDormId = $dorm->id;
                // Find first active resident/student in this dorm
                $resident = \App\Models\DormitoryResident::where('dormitory_id', $sampleDormId)
                    ->where('is_active', true)
                    ->with('student')
                    ->first();
                if ($resident && $resident->student) {
                    $sampleStudent = $resident->student;
                    $sampleStudentId = $sampleStudent->id;

                    // Use rules engine to get current usage
                    $engine = \App\Domain\Services\BoardingRulesEngine::getInstance();
                    $currentUsageVisit = $engine->countUsageForCurrentPeriod(
                        $sampleStudentId, 'visit', $sampleDormId, $policy->visit_quota_period ?? 'monthly'
                    );
                    $currentUsageLeave = $engine->countUsageForCurrentPeriod(
                        $sampleStudentId, 'leave', $sampleDormId, $policy->leave_quota_period ?? 'weekly'
                    );

                    $visitUsage = $currentUsageVisit;
                    $leaveUsage = $currentUsageLeave;

                    // Calculate remaining based on policy quota
                    if ($policy->visit_strategy === 'quota' && $policy->visit_quota) {
                        $remainingVisit = max(0, $policy->visit_quota - $currentUsageVisit);
                    }
                    if ($policy->leave_strategy === 'quota' && $policy->leave_quota) {
                        $remainingLeave = max(0, $policy->leave_quota - $currentUsageLeave);
                    }
                }
            }
        }

        $userId = $request->route('userId');

        return view('dormitory.policies.show', compact('policy', 'userId', 'sampleStudent', 'visitUsage', 'leaveUsage', 'remainingVisit', 'remainingLeave', 'sampleStudentId', 'sampleDormId'));
    }

    public function edit(Request $request, string $id)
    {
        $policy = BoardingPolicy::with('assignments.dormitory')->findOrFail($id);
        $dormitories = Dormitory::where('is_active', true)->orderBy('name')->get();

        $assignedDormIds = $policy->assignments->pluck('target_id')->toArray();
        $userId = $request->route('userId');

        return view('dormitory.policies.edit', compact('policy', 'dormitories', 'assignedDormIds', 'userId'));
    }

    public function update(Request $request, string $id)
    {
        $policy = BoardingPolicy::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:64', Rule::unique('boarding_policies', 'code')->ignore($policy->id)],
            'description' => 'nullable|string',
            'leave_strategy' => 'required|in:quota,unrestricted,banned',
            'leave_quota' => 'nullable|integer|min:0|required_if:leave_strategy,quota',
            'leave_quota_period' => 'required_if:leave_strategy,quota|in:weekly,monthly,semester,yearly',
            'visit_strategy' => 'required|in:quota,unrestricted,banned',
            'visit_quota' => 'nullable|integer|min:0|required_if:visit_strategy,quota',
            'visit_quota_period' => 'required_if:visit_strategy,quota|in:daily,weekly,monthly',
            'max_visitors_per_visit' => 'nullable|integer|min:1',
            'curfew_hour' => 'nullable|integer|min:0|max:23',
            'special_permission_allowed' => 'boolean',
            'special_permission_types' => 'nullable|array',
            'special_permission_types.*' => 'in:medical,emergency,family,competition,school_activity',
            'auto_sync_academic_attendance' => 'boolean',
            'is_active' => 'boolean',
            'dormitory_ids' => 'sometimes|array',
            'dormitory_ids.*' => 'exists:dormitories,id',
        ]);

        $policy->update(array_filter($validated, fn ($v, $k) => in_array($k, [
            'name', 'code', 'description', 'leave_strategy', 'leave_quota', 'leave_quota_period',
            'visit_strategy', 'visit_quota', 'visit_quota_period', 'max_visitors_per_visit',
            'curfew_hour', 'special_permission_allowed', 'special_permission_types',
            'auto_sync_academic_attendance', 'is_active',
        ], true)));

        // Update dormitory assignments
        if ($request->filled('dormitory_ids')) {
            $existingIds = $policy->assignments->pluck('target_id')->toArray();
            $newIds = $request->dormitory_ids;

            // Remove old assignments not in new list
            $toRemove = array_diff($existingIds, $newIds);
            foreach ($toRemove as $rmId) {
                $policy->assignments()->where('target_id', $rmId)->where('policy_assignment_type', 'dormitory')->delete();
            }

            // Add new assignments
            $added = array_diff($newIds, $existingIds);
            foreach ($added as $nId) {
                $policy->assignments()->create([
                    'policy_assignment_type' => 'dormitory',
                    'target_id' => $nId,
                    'effective_from' => now(),
                    'priority' => 0,
                ]);
            }
        }

        return redirect()->route('user.boarding-policies.index')
            ->with('success', 'Kebijakan asrama berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $policy = BoardingPolicy::findOrFail($id);
        $policy->assignments()->delete();
        $policy->delete();

        return redirect()->route('user.boarding-policies.index')
            ->with('success', 'Kebijakan asrama berhasil dihapus.');
    }
}
