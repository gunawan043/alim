<?php

namespace App\Services;

use App\Models\ApprovalAction;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public function start(Model $model, string $flowCode): ApprovalRequest
    {
        $flow = ApprovalFlow::where('code', $flowCode)
            ->where('is_active', true)
            ->firstOrFail();

        $firstStep = $flow->steps()
            ->orderBy('step_order')
            ->firstOrFail();

        return ApprovalRequest::create([
            'approval_flow_id' => $flow->id,
            'requestable_type' => $model::class,
            'requestable_id' => $model->id,
            'status' => 'pending',
            'current_step_id' => $firstStep->id,
            'requested_by' => Auth::id(),
        ]);
    }

    public function approve(
        ApprovalRequest $approvalRequest,
        User $user,
        ?string $note = null
    ): void {
        if ($approvalRequest->status !== 'pending') {
            abort(403, 'Approval sudah selesai');
        }

        $currentStep = $approvalRequest->currentStep;

        if (! $currentStep) {
            abort(403, 'Step approval tidak valid');
        }

        // 🔐 ROLE CHECK
        if (
            ! canPermission($currentStep->role_name) ||
            $user->role_level > $currentStep->min_role_level
        ) {
            abort(403, 'Anda tidak berwenang melakukan approval ini');
        }

        DB::transaction(function () use (
            $approvalRequest,
            $user,
            $note,
            $currentStep
        ) {

            // LOG ACTION
            ApprovalAction::create([
                'approval_request_id' => $approvalRequest->id,
                'user_id' => $user->id,
                'action' => 'approved',
                'note' => $note,
                'step_order' => $currentStep->step_order,
            ]);

            // NEXT STEP
            $nextStep = ApprovalFlowStep::where('approval_flow_id', $approvalRequest->approval_flow_id)
                ->where('step_order', '>', $currentStep->step_order)
                ->orderBy('step_order')
                ->first();

            if ($nextStep) {
                // 🔁 PINDAH STEP
                $approvalRequest->update([
                    'current_step_id' => $nextStep->id,
                ]);
            } else {
                // ✅ FINAL APPROVAL
                $approvalRequest->update([
                    'status' => 'approved',
                    'current_step_id' => null,
                ]);

                // UPDATE TARGET ENTITY
                $approvalRequest->requestable->update([
                    'status' => 'approved',
                ]);
            }
        });
    }

    public function reject(
        ApprovalRequest $approvalRequest,
        User $user,
        string $note
    ): void {
        if ($approvalRequest->status !== 'pending') {
            abort(403, 'Approval sudah selesai');
        }

        $currentStep = $approvalRequest->currentStep;

        if (! $currentStep) {
            abort(403, 'Step approval tidak valid');
        }

        if (
            ! canPermission($currentStep->role_name) ||
            $user->role_level > $currentStep->min_role_level
        ) {
            abort(403, 'Anda tidak berwenang menolak approval ini');
        }

        DB::transaction(function () use (
            $approvalRequest,
            $user,
            $note,
            $currentStep
        ) {

            ApprovalAction::create([
                'approval_request_id' => $approvalRequest->id,
                'user_id' => $user->id,
                'action' => 'rejected',
                'note' => $note,
                'step_order' => $currentStep->step_order,
            ]);

            $approvalRequest->update([
                'status' => 'rejected',
                'current_step_id' => null,
            ]);

            $approvalRequest->requestable->update([
                'status' => 'rejected',
            ]);
        });
    }
}
