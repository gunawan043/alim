<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceApproval extends Model
{
    use HasFactory;

    protected $table = 'invoice_approvals';

    protected $fillable = [
        'approval_number',
        'vendor_id',
        'purchase_order_id',
        'invoice_number',
        'supplier_invoice_number',
        'attachment_path',
        'total_amount',
        'currency',
        'tax_amount',
        'discount_amount',
        'invoice_date',
        'due_date',
        'status',
        'notes',
        'comments',
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'paid_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PARTIALLY_APPROVED = 'partially_approved';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(InvoiceApprovalStep::class, 'invoice_approval_id');
    }

    public function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "IA-{$year}{$month}-";

        $latest = static::where('approval_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('approval_number');

        $sequence = 1;
        if ($latest) {
            $sequence = ((int) substr($latest, strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function hasPendingSteps(): bool
    {
        if ($this->relationLoaded('steps')) {
            return $this->steps->contains(fn ($s) => $s->status === 'pending');
        }

        return static::where('id', $this->id)
            ->whereHas('steps', function ($q) {
                $q->where('status', 'pending');
            })
            ->exists();
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPaidOrFinal(): bool
    {
        return in_array($this->status, [
            self::STATUS_PAID,
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED,
        ], true);
    }
}
