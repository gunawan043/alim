<?php

namespace App\Services;

use App\Models\ApprovalAction;
use App\Models\ApprovalFlow;
use App\Models\ApprovalRequest;
use App\Models\GtkTransferRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Alur approval berbasis tahapan (approval_actions).
 *
 * Skema yang dipakai sesuai tabel existing:
 * - approval_requests : request_type, reference_id, requested_by, status (PENDING/APPROVED/REJECTED)
 * - approval_actions  : step_order, role_name, step_permission, approved_by, action (PENDING/APPROVED/REJECTED), action_at, note
 */
class ApprovalService
{
    /**
     * Mulai alur approval untuk sebuah model berdasarkan kode flow.
     */
    public function start(Model $model, string $flowCode): ApprovalRequest
    {
        $flow = ApprovalFlow::where('code', $flowCode)
            ->where('is_active', true)
            ->firstOrFail();

        $steps = $flow->steps()->orderBy('step_order')->get();

        if ($steps->isEmpty()) {
            throw new \RuntimeException("Flow [{$flowCode}] belum memiliki tahapan approval.");
        }

        return DB::transaction(function () use ($model, $flow, $flowCode, $steps) {
            $request = ApprovalRequest::create([
                'request_type'     => strtoupper($flowCode),
                'reference_id'     => (string) $model->getKey(),
                'requested_by'     => Auth::id(),
                'status'           => 'PENDING',
                'approval_flow_id' => $flow->id,
                'requestable_type' => $model::class,
                'requestable_id'   => (string) $model->getKey(),
                'current_step_id'  => $steps->first()->id,
            ]);

            foreach ($steps as $step) {
                ApprovalAction::create([
                    'approval_request_id' => $request->id,
                    'step_order'          => $step->step_order,
                    'role_name'           => $step->role_name,
                    'step_permission'     => $step->step_permission,
                    'action'              => 'PENDING',
                ]);
            }

            return $request;
        });
    }

    /**
     * Setujui tahap yang sedang menunggu.
     */
    public function approve(ApprovalRequest $approvalRequest, User $user, ?string $note = null): void
    {
        abort_if($approvalRequest->status !== 'PENDING', 403, 'Approval sudah selesai.');

        $action = $this->pendingAction($approvalRequest);
        abort_if(! $action, 403, 'Tidak ada tahap approval yang menunggu.');

        $this->assertCanAct($action, $user);

        // Tahap sebelumnya wajib sudah APPROVED
        $previous = ApprovalAction::where('approval_request_id', $approvalRequest->id)
            ->where('step_order', '<', $action->step_order)
            ->orderByDesc('step_order')
            ->first();

        abort_if($previous && $previous->action !== 'APPROVED', 403, 'Tahap sebelumnya belum disetujui.');

        DB::transaction(function () use ($approvalRequest, $action, $user, $note) {
            $action->update([
                'approved_by' => $user->id,
                'user_id'     => $user->id,
                'action'      => 'APPROVED',
                'action_at'   => now(),
                'note'        => $note,
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);

            $next = $this->pendingAction($approvalRequest->fresh(['actions']));

            $approvalRequest->update([
                'current_step_id' => $next?->id,
                'status'          => $next ? 'PENDING' : 'APPROVED',
            ]);

            if (! $next) {
                $this->executeIfComplete($approvalRequest->fresh());
            }
        });
    }

    /**
     * Tolak tahap yang sedang menunggu (langsung menutup request).
     */
    public function reject(ApprovalRequest $approvalRequest, User $user, ?string $note = null): void
    {
        abort_if($approvalRequest->status !== 'PENDING', 403, 'Approval sudah selesai.');

        $action = $this->pendingAction($approvalRequest);
        abort_if(! $action, 403, 'Tidak ada tahap approval yang menunggu.');

        $this->assertCanAct($action, $user);

        DB::transaction(function () use ($approvalRequest, $action, $user, $note) {
            $action->update([
                'approved_by' => $user->id,
                'user_id'     => $user->id,
                'action'      => 'REJECTED',
                'action_at'   => now(),
                'note'        => $note,
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);

            $approvalRequest->update([
                'status'          => 'REJECTED',
                'current_step_id' => null,
            ]);
        });
    }

    // =====================================================================
    // HELPERS
    // =====================================================================

    protected function pendingAction(ApprovalRequest $approvalRequest): ?ApprovalAction
    {
        return ApprovalAction::where('approval_request_id', $approvalRequest->id)
            ->where('action', 'PENDING')
            ->orderBy('step_order')
            ->first();
    }

    /**
     * Wewenang tahap: permission tahap → role name → system/super admin.
     */
    protected function assertCanAct(ApprovalAction $action, User $user): void
    {
        if (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin()) {
            return;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }

        if ($action->step_permission && canPermission($action->step_permission)) {
            return;
        }

        if ($action->role_name && method_exists($user, 'hasRole') && $user->hasRole($action->role_name)) {
            return;
        }

        abort(403, 'Anda tidak berwenang melakukan approval pada tahap ini.');
    }

    /**
     * Efek samping ketika seluruh tahap selesai (APPROVED).
     */
    protected function executeIfComplete(ApprovalRequest $approvalRequest): void
    {
        try {
            $transferId = $approvalRequest->requestable_id ?: $approvalRequest->reference_id;
            $isTransfer = $approvalRequest->requestable_type === GtkTransferRequest::class
                || $approvalRequest->request_type === 'TRANSFER'
                || $approvalRequest->request_type === 'GTK_TRANSFER';

            if ($isTransfer && $transferId) {
                $transfer = GtkTransferRequest::find($transferId);

                if ($transfer) {
                    $transfer->update([
                        'status'       => 'approved',
                        'approved_by'  => Auth::id(),
                        'approved_at'  => now(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Approval executeIfComplete failed: ' . $e->getMessage());
        }
    }
}
