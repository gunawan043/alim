<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\InvoiceApproval;
use App\Models\InvoiceApprovalStep;
use App\Models\Notification;
use App\Services\Sarpras\StateMachine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class InvoiceApprovalService
{
    public const TRANSITIONS = [
        'invoice' => [
            InvoiceApproval::STATUS_DRAFT => [
                InvoiceApproval::STATUS_SUBMITTED,
                InvoiceApproval::STATUS_CANCELLED,
            ],
            InvoiceApproval::STATUS_SUBMITTED => [
                InvoiceApproval::STATUS_IN_REVIEW,
                InvoiceApproval::STATUS_CANCELLED,
            ],
            InvoiceApproval::STATUS_IN_REVIEW => [
                InvoiceApproval::STATUS_APPROVED,
                InvoiceApproval::STATUS_REJECTED,
                InvoiceApproval::STATUS_PARTIALLY_APPROVED,
            ],
            InvoiceApproval::STATUS_APPROVED => [
                InvoiceApproval::STATUS_PAID,
                InvoiceApproval::STATUS_CANCELLED,
            ],
        ],
    ];

    public function __construct(private StateMachine $machine) {}

    public function create(int $userId, int $vendorId, array $data): InvoiceApproval
    {
        $invoice = new InvoiceApproval;
        $invoice->setAttribute('approval_number', $invoice->generateNumber());
        $invoice->setAttribute('vendor_id', $vendorId);
        $invoice->setAttribute('purchase_order_id', $data['purchase_order_id'] ?? null);
        $invoice->setAttribute('invoice_number', $data['invoice_number'] ?? null);
        $invoice->setAttribute('supplier_invoice_number', $data['supplier_invoice_number'] ?? null);
        $invoice->setAttribute('total_amount', $data['total_amount'] ?? 0);
        $invoice->setAttribute('currency', $data['currency'] ?? 'IDR');
        $invoice->setAttribute('tax_amount', $data['tax_amount'] ?? 0);
        $invoice->setAttribute('discount_amount', $data['discount_amount'] ?? 0);
        $invoice->setAttribute('invoice_date', $data['invoice_date'] ?? now()->toDateString());
        $invoice->setAttribute('due_date', $data['due_date'] ?? null);
        $invoice->setAttribute('status', InvoiceApproval::STATUS_DRAFT);
        $invoice->setAttribute('notes', $data['notes'] ?? null);
        $invoice->setAttribute('submitted_by', $userId);
        $invoice->save();

        $this->audit($invoice, 'created');

        return $invoice;
    }

    public function uploadAttachment(InvoiceApproval $invoice, UploadedFile $file): string
    {
        $path = $file->store('invoices/attachments', 'public');
        $invoice->update(['attachment_path' => $path]);
        $this->audit($invoice, 'attachment_uploaded');

        return $path;
    }

    public function submit(InvoiceApproval $invoice, int $userId): InvoiceApproval
    {
        $this->machine->assert('invoice', $invoice->status, InvoiceApproval::STATUS_SUBMITTED);

        $invoice->update([
            'status' => InvoiceApproval::STATUS_SUBMITTED,
            'submitted_by' => $userId,
            'submitted_at' => now(),
        ]);

        $this->audit($invoice, 'submitted');

        return $invoice;
    }

    public function startReview(InvoiceApproval $invoice, array $reviewers, string $roleRequired = 'reviewer'): InvoiceApproval
    {
        $this->machine->assert('invoice', $invoice->status, InvoiceApproval::STATUS_IN_REVIEW);

        return DB::transaction(function () use ($invoice, $reviewers, $roleRequired) {
            $invoice->update(['status' => InvoiceApproval::STATUS_IN_REVIEW]);

            $existingSteps = $invoice->steps()->count();
            $stepOrder = $existingSteps + 1;

            foreach ($reviewers as $reviewerId) {
                $step = InvoiceApprovalStep::create([
                    'invoice_approval_id' => $invoice->id,
                    'assigned_to' => $reviewerId,
                    'step_order' => $stepOrder++,
                    'role_required' => $roleRequired,
                    'status' => InvoiceApprovalStep::STATUS_PENDING,
                ]);

                $this->audit($invoice, 'step_created', ['step_id' => $step->id, 'assigned_to' => $reviewerId]);
            }

            return $invoice;
        });
    }

    public function approveStep(InvoiceApprovalStep $step, ?string $comments = null): InvoiceApprovalStep
    {
        return DB::transaction(function () use ($step, $comments) {
            $step->update([
                'status' => InvoiceApprovalStep::STATUS_APPROVED,
                'approval_comments' => $comments,
                'completed_at' => now(),
            ]);

            $this->audit($step->invoiceApproval, 'step_approved', ['step_id' => $step->id]);

            $this->checkCompletion($step->invoiceApproval);

            return $step;
        });
    }

    public function rejectStep(InvoiceApprovalStep $step, string $reason): InvoiceApprovalStep
    {
        return DB::transaction(function () use ($step, $reason) {
            $step->update([
                'status' => InvoiceApprovalStep::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'completed_at' => now(),
            ]);

            // Reject the entire invoice
            $step->invoiceApproval->update([
                'status' => InvoiceApproval::STATUS_REJECTED,
                'reviewed_at' => now(),
                'comments' => $reason,
            ]);

            $this->audit($step->invoiceApproval, 'step_rejected', ['step_id' => $step->id, 'reason' => $reason]);

            return $step;
        });
    }

    public function markPaid(InvoiceApproval $invoice, ?string $paymentRef = null): InvoiceApproval
    {
        $this->machine->assert('invoice', $invoice->status, InvoiceApproval::STATUS_PAID);

        $invoice->update([
            'status' => InvoiceApproval::STATUS_PAID,
            'paid_at' => now(),
            'comments' => $paymentRef ? "Payment ref: {$paymentRef}" : $invoice->comments,
        ]);

        $this->audit($invoice, 'paid', ['reference' => $paymentRef]);

        return $invoice;
    }

    public function cancel(InvoiceApproval $invoice, string $reason): InvoiceApproval
    {
        $this->machine->assert('invoice', $invoice->status, InvoiceApproval::STATUS_CANCELLED);

        $invoice->update([
            'status' => InvoiceApproval::STATUS_CANCELLED,
            'comments' => $reason,
        ]);

        $this->audit($invoice, 'cancelled');

        return $invoice;
    }

    public function getPendingForUser(string $userId): array
    {
        $steps = InvoiceApprovalStep::where('assigned_to', $userId)
            ->where('status', InvoiceApprovalStep::STATUS_PENDING)
            ->get();

        return $steps->all();
    }

    public function getAgingReport(): array
    {
        $now = now();
        $pending = InvoiceApproval::whereIn('status', [
            InvoiceApproval::STATUS_SUBMITTED,
            InvoiceApproval::STATUS_IN_REVIEW,
            InvoiceApproval::STATUS_APPROVED,
        ])->get();

        $aging = [
            '0-30' => [],
            '31-60' => [],
            '61-90' => [],
            '90+' => [],
        ];

        foreach ($pending as $invoice) {
            $days = $invoice->submitted_at ? $invoice->submitted_at->diffInDays($now) : 0;

            if ($days <= 30) {
                $aging['0-30'][] = $invoice;
            } elseif ($days <= 60) {
                $aging['31-60'][] = $invoice;
            } elseif ($days <= 90) {
                $aging['61-90'][] = $invoice;
            } else {
                $aging['90+'][] = $invoice;
            }
        }

        return $aging;
    }

    private function checkCompletion(InvoiceApproval $invoice): void
    {
        $steps = $invoice->steps()->get();
        $hasRejected = $steps->contains(fn ($s) => $s->status === InvoiceApprovalStep::STATUS_REJECTED);
        $allApproved = $steps->every(fn ($s) => $s->status === InvoiceApprovalStep::STATUS_APPROVED);

        if ($allApproved) {
            $invoice->update([
                'status' => InvoiceApproval::STATUS_APPROVED,
                'reviewed_at' => now(),
            ]);

            Notification::create([
                'user_id' => $invoice->submitted_by,
                'type' => 'invoice_approved',
                'title' => 'Invoice Approved',
                'message' => "Invoice {$invoice->approval_number} has been approved",
                'data' => ['invoice_id' => $invoice->id],
            ]);

            $this->audit($invoice, 'all_approved');
        } elseif ($steps->some(fn ($s) => $s->status === InvoiceApprovalStep::STATUS_APPROVED) && ! $allApproved) {
            $invoice->update([
                'status' => InvoiceApproval::STATUS_PARTIALLY_APPROVED,
            ]);

            $this->audit($invoice, 'partially_approved');
        }
    }

    private function audit(InvoiceApproval $invoice, string $action, array $meta = []): void
    {
        AuditTrail::create([
            'actor_id' => auth()->id(),
            'action' => "invoice.{$action}",
            'entity_type' => InvoiceApproval::class,
            'entity_id' => $invoice->id,
            'metadata' => array_merge([
                'approval_number' => $invoice->approval_number,
                'status' => $invoice->status,
            ], $meta),
        ]);
    }
}
