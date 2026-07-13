<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Services\Boarding\BoardingApprovalService;
use App\Services\Boarding\HealthWorkflowService;
use App\Services\Boarding\LeaveWorkflowService;
use App\Services\Boarding\VisitWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardingApprovalCenterController extends Controller
{
    public function __construct(
        private readonly BoardingApprovalService $inbox,
        private readonly LeaveWorkflowService $leave,
        private readonly VisitWorkflowService $visits,
        private readonly HealthWorkflowService $health,
    ) {}

    /**
     * Unified inbox view for one dormitory.
     * URL: {userId}/asrama/{asramaUuid}/approval-center
     */
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $asrama = Dormitory::findOrFail($asramaUuid);

        $items = $this->inbox->pending($asramaUuid);
        $counts = $this->inbox->counts($asramaUuid);

        $counts['total'] = $counts['leave'] + $counts['visit'] + $counts['health'];

        return view('boarding.approval-center', [
            'items' => $items,
            'counts' => $counts,
            'userId' => $userId,
            'asramaUuid' => $asrama->id,
            'asrama' => $asrama,
        ]);
    }

    /**
     * Approve a single pending request by type+id.
     */
    public function approve(Request $request, string $userId, string $asramaUuid)
    {
        $request->validate([
            'type' => 'required|in:leave,visit,health',
            'id' => 'required|string',
            'note' => 'nullable|string|max:500',
        ]);

        $type = $request->input('type');
        $id = $request->input('id');
        $note = $request->input('note');

        try {
            DB::transaction(function () use ($type, $id, $note, $asramaUuid) {
                switch ($type) {
                    case 'leave':
                        $this->leave->approve($id, $asramaUuid, $note);
                        break;
                    case 'visit':
                        $this->visits->approve($id, $asramaUuid, $note);
                        break;
                    case 'health':
                        $this->health->approve($id, $note);
                        break;
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['approval' => $e->getMessage()])->withInput();
        }

        return redirect()->route('user.asrama.approval-center', [$userId, $asramaUuid])
            ->with('success', 'Permohonan telah disetujui.');
    }

    /**
     * Reject a single pending request by type+id.
     */
    public function reject(Request $request, string $userId, string $asramaUuid)
    {
        $request->validate([
            'type' => 'required|in:leave,visit,health',
            'id' => 'required|string',
            'reason' => 'required|string|max:500',
        ]);

        $type = $request->input('type');
        $id = $request->input('id');
        $reason = $request->input('reason');

        try {
            DB::transaction(function () use ($type, $id, $reason, $asramaUuid) {
                switch ($type) {
                    case 'leave':
                        $this->leave->reject($id, $asramaUuid, $reason);
                        break;
                    case 'visit':
                        $this->visits->reject($id, $asramaUuid, $reason);
                        break;
                    case 'health':
                        $this->health->reject($id, $reason);
                        break;
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['approval' => $e->getMessage()])->withInput();
        }

        return redirect()->route('user.asrama.approval-center', [$userId, $asramaUuid])
            ->with('success', 'Permohonan telah ditolak.');
    }
}