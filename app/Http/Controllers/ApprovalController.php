<?php

namespace App\Http\Controllers;

use App\Authorization\Services\ApprovalRoleResolver;
use App\Models\ApprovalAction;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\GtkTransferRequest;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $query = ApprovalRequest::with(['requestedBy', 'actions'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('requestedBy', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('request_type', $request->type);
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('approvals.index', compact('requests'));
    }

    public function myPending(Request $request)
    {
        $user = auth()->user();
        $query = ApprovalRequest::with(['requestedBy', 'actions'])
            ->where('status', 'PENDING')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('requestedBy', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('approvals.my-pending', compact('requests'));
    }

    public function history(Request $request, ?string $userId = null)
    {
        $user = auth()->user();
        $query = ApprovalRequest::with(['requestedBy', 'actions'])
            ->where('status', '!=', 'PENDING')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('requestedBy', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('approvals.history', compact('requests'));
    }

    public function show(string $approvalUuid)
    {
        $approval = ApprovalRequest::with(['requestedBy', 'actions'])->findOrFail($approvalUuid);

        return view('approvals.show', compact('approval'));
    }

    public function track(string $approvalUuid)
    {
        $approval = ApprovalRequest::with([
            'flow.steps',
            'actions' => fn ($q) => $q->orderBy('created_at'),
            'actions.actionBy',
            'requestable',
        ])->where('uuid', $approvalUuid)->firstOrFail();

        return view('approvals.track', compact('approval'));
    }

    public function createApprovalFlow()
    {
        $flow = ApprovalFlow::create(['name' => 'Transfer GTK']);

        $steps = [
            ['order' => 1, 'role_identifier' => 'Kepala Sekolah', 'level' => 6],
            ['order' => 2, 'role_identifier' => 'Wadir 1', 'level' => 3],
            ['order' => 3, 'role_identifier' => 'Mudir', 'level' => 2],
        ];

        foreach ($steps as $step) {
            ApprovalFlowStep::create([
                'approval_flow_id' => $flow->id,
                'step_order' => $step['order'],
                'role_name' => $step['role_identifier'],
                'step_permission' => ApprovalRoleResolver::resolvePermission($step['role_identifier'])[0] ?? null,
                'min_role_level' => $step['level'],
            ]);
        }
    }

    public function generateApproval($transferRequest)
    {
        $approval = ApprovalRequest::create([
            'request_type' => 'GTK_TRANSFER',
            'reference_id' => $transferRequest->id,
            'requested_by' => Auth::id(),
        ]);

        $steps = ApprovalFlowStep::whereHas('flow', fn ($q) => $q->where('name', 'Transfer GTK')
        )->orderBy('step_order')->get();

        foreach ($steps as $step) {
            ApprovalAction::create([
                'approval_request_id' => $approval->id,
                'step_order' => $step->step_order,
                'role_name' => $step->role_name,
                'step_permission' => $step->step_permission,
            ]);
        }

        return $approval;
    }

    public function approveStep(ApprovalAction $action)
    {
        $user = auth()->user();

        abort_if(
            ! $this->canApproveStep($action),
            403,
            'Tidak berhak approve tahap ini'
        );

        // Cek step sebelumnya
        $previous = ApprovalAction::where(
            'approval_request_id',
            $action->approval_request_id
        )->where('step_order', $action->step_order - 1)->first();

        if ($previous && $previous->action !== 'APPROVED') {
            abort(403, 'Tahap sebelumnya belum disetujui');
        }

        $action->update([
            'approved_by' => $user->id,
            'action' => 'APPROVED',
            'action_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Jika terakhir → EKSEKUSI
        if ($this->isLastStep($action)) {
            $this->executeTransfer($action);
        }

        return response()->json(['message' => 'Approval berhasil']);
    }

    private function canApproveStep(ApprovalAction $action): bool
    {
        $permission = $action->step_permission
            ?? (ApprovalRoleResolver::resolvePermission($action->role_name)[0] ?? null);

        if ($permission === null) {
            return false;
        }

        return canPermission($permission);
    }

    private function executeTransfer(ApprovalAction $action)
    {
        DB::transaction(function () use ($action) {

            $approval = $action->approvalRequest;
            $transfer = GtkTransferRequest::findOrFail($approval->reference_id);

            // 🔥 PINDAHKAN GTK
            app(PersonaliaController::class)
                ->executeApprovedTransfer($transfer);

            $approval->update(['status' => 'APPROVED']);
        });
    }

    public function approve(Request $request, ApprovalRequest $approvalRequest)
    {
        $this->authorize('approve', $approvalRequest);

        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($approvalRequest, $request) {
            app(ApprovalService::class)
                ->approve($approvalRequest, auth()->user(), $request->note);
        });

        return response()->json([
            'message' => 'Approval berhasil diproses',
        ]);
    }

    public function reject(Request $request, ApprovalRequest $approvalRequest)
    {
        $this->authorize('reject', $approvalRequest);

        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        DB::transaction(function () use ($approvalRequest, $request) {
            app(ApprovalService::class)
                ->reject($approvalRequest, auth()->user(), $request->note);
        });

        return response()->json([
            'message' => 'Approval ditolak',
        ]);
    }
}
