<?php

namespace App\Http\Controllers;

use App\Models\BoardingPolicy;
use App\Models\Dormitory;
use App\Models\DormitoryPolicyAssignment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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

    public function create()
    {
        $dormitories = Dormitory::where('is_active', true)->orderBy('name')->get();

        $dormitoryOptions = BoardingPolicy::pluck('assigned_dormitories', 'id')
            ->map(fn($d) => $d ?? collect())
            ->keyBy('id');

        return view('dormitory.policies.create', compact('dormitories', 'dormitoryOptions'));
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

        $policy = BoardingPolicy::create(array_filter($validated, fn($v, $k) => in_array($k, [
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

    public function show(string $id)
    {
        $policy = BoardingPolicy::with([
            'assignments.dormitory',
            'assignments' => fn($q) => $q->orderByDesc('created_at')->take(10),
        ])->findOrFail($id);

        return view('dormitory.policies.show', compact('policy'));
    }

    public function edit(string $id)
    {
        $policy = BoardingPolicy::with('assignments.dormitory')->findOrFail($id);
        $dormitories = Dormitory::where('is_active', true)->orderBy('name')->get();

        $assignedDormIds = $policy->assignments->pluck('target_id')->toArray();

        return view('dormitory.policies.edit', compact('policy', 'dormitories', 'assignedDormIds'));
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

        $policy->update(array_filter($validated, fn($v, $k) => in_array($k, [
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
