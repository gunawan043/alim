<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Dormitory\UpdateStaffScopeRequest;
use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DormitoryStaffAssignmentController extends Controller
{
    public function index(Request $request, string $targetUserId): View
    {
        abort_unless($this->isAuthorizedToManage($request, $targetUserId), 403);

        $user = User::withTrashed()->findOrFail($targetUserId);
        $dormitories = Dormitory::where('is_active', true)->get();
        $existingAssignments = DormitoryStaffAssignment::where('user_id', $targetUserId)
            ->with('dormitory')
            ->get()
            ->keyBy(fn ($a) => $a->dormitory_id);

        return view('dormitory.staff-assignments.index', compact('user', 'dormitories', 'existingAssignments'));
    }

    public function update(UpdateStaffScopeRequest $request): RedirectResponse
    {
        $targetUserId = (string) $request->route('targetUserId');

        $dormitoryIds = $request->input('dormitory_ids', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $notes = $request->input('notes');

        // Clear existing active assignments for this user
        DormitoryStaffAssignment::where('user_id', $targetUserId)
            ->where('status', 'active')
            ->update(['status' => 'ended']);

        // Create new assignments
        foreach ($dormitoryIds as $dormitoryId) {
            DormitoryStaffAssignment::create([
                'user_id' => $targetUserId,
                'dormitory_id' => $dormitoryId,
                'assigned_by_id' => $request->user()?->id ?? null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
                'notes' => $notes,
            ]);
        }

        return back()->with('success', 'Staf Perizinan scope updated successfully.');
    }

    public function destroy(Request $request, string $userId, string $targetUserId, string $dormitoryId): RedirectResponse
    {

        abort_unless($this->isAuthorizedToManage($request, $targetUserId), 403);

        $assignment = DormitoryStaffAssignment::where('user_id', $targetUserId)
            ->where('dormitory_id', $dormitoryId)
            ->withTrashed()
            ->firstOrFail();

        $assignment->update(['status' => 'ended']);

        return back()->with('success', 'Assignment removed successfully.');
    }

    private function isAuthorizedToManage(Request $request, string $userId): bool
    {
        $currentUser = $request->user();

        // Super Admin can manage anyone
        if ($currentUser->isSystemAdmin() || (method_exists($currentUser, 'isSuperAdmin') && $currentUser->isSuperAdmin())) {
            return true;
        }

        // Kepala Asrama can manage Staf Perizinan in their dormitory
        $targetUser = User::withTrashed()->findOrFail($userId);

        // Check if current user is Kepala Asrama of any dormitory
        $myDormitories = Dormitory::where('head_id', $currentUser->id)->pluck('id')->toArray();

        if (empty($myDormitories)) {
            return false;
        }

        // Check if target user has Staf Perizinan assignment in any of my dormitories
        return DormitoryStaffAssignment::where('user_id', $userId)
            ->whereIn('dormitory_id', $myDormitories)
            ->exists();
    }
}
