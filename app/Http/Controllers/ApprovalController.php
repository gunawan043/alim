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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
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

        // Hanya tampilkan yang benar-benar bisa di-approve oleh user ini.
        $actionable = $query->get()
            ->filter(fn (ApprovalRequest $req) => $this->canActOn($req, $user))
            ->values();

        $perPage = 15;
        $page = Paginator::resolveCurrentPage() ?: 1;

        $requests = new LengthAwarePaginator(
            $actionable->forPage($page, $perPage)->values(),
            $actionable->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('approvals.my-pending', compact('requests'));
    }

    /**
     * Apakah user berwenang pada tahap yang sedang menunggu.
     */
    protected function canActOn(ApprovalRequest $request, $user): bool
    {
        if ((method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return true;
        }

        $step = $request->currentStep();

        if (! $step) {
            return false;
        }

        if ($step->step_permission && canPermission($step->step_permission)) {
            return true;
        }

        return $step->role_name && method_exists($user, 'hasRole') && $user->hasRole($step->role_name);
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

    public function show(string $userId, string $approvalUuid)
    {
        $approval = ApprovalRequest::with(['requestedBy', 'actions'])->findOrFail($approvalUuid);

        return view('approvals.show', compact('approval'));
    }

    public function track(string $userId, string $approvalUuid)
    {
        $approval = ApprovalRequest::with([
            'flow.steps',
            'actions' => fn ($q) => $q->orderBy('created_at'),
            'actions.approvedBy',
            'requestedBy',
            'requestable',
        ])->findOrFail($approvalUuid);

        return view('approvals.track', compact('approval'));
    }

    public function createApprovalFlow()
    {
        $flow = ApprovalFlow::create(['name' => 'Transfer GTK']);

        $steps = [
            ['order' => 1, 'role_identifier' => 'Kepala Sekolah', 'level' => 6],
            ['order' => 2, 'role_identifier' => 'Wakil Mudir I', 'level' => 3],
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

    public function approve(Request $request, string $userId, string $approvalUuid)
    {
        $approvalRequest = ApprovalRequest::findOrFail($approvalUuid);

        $this->authorize('approve', $approvalRequest);

        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($approvalRequest, $request) {
                app(ApprovalService::class)
                    ->approve($approvalRequest, Auth::user(), $request->note);
            });
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', 'Approval gagal: ' . $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Approval berhasil diproses']);
        }

        return redirect()
            ->route('user.approvals.show', ['userId' => auth()->id(), 'approvalUuid' => $approvalRequest->id])
            ->with('success', 'Approval berhasil diproses.');
    }

    public function reject(Request $request, string $userId, string $approvalUuid)
    {
        $approvalRequest = ApprovalRequest::findOrFail($approvalUuid);

        $this->authorize('reject', $approvalRequest);

        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($approvalRequest, $request) {
                app(ApprovalService::class)
                    ->reject($approvalRequest, Auth::user(), $request->note);
            });
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', 'Penolakan gagal: ' . $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Approval ditolak']);
        }

        return redirect()
            ->route('user.approvals.show', ['userId' => auth()->id(), 'approvalUuid' => $approvalRequest->id])
            ->with('success', 'Pengajuan ditolak.');
    }
}
