<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceApprovalStep extends Model
{
    use HasFactory;

    protected $table = 'invoice_approval_steps';

    protected $fillable = [
        'invoice_approval_id',
        'assigned_to',
        'step_order',
        'role_required',
        'status',
        'approval_comments',
        'rejection_reason',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SKIPPED = 'skipped';

    public function invoiceApproval(): BelongsTo
    {
        return $this->belongsTo(InvoiceApproval::class, 'invoice_approval_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
