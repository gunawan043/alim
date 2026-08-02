<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfqRequest extends Model
{
    use HasFactory;

    protected $table = 'rfq_requests';

    protected $fillable = [
        'rfq_number',
        'title',
        'description',
        'status',
        'quotation_deadline',
        'expected_delivery_date',
        'delivery_location',
        'terms_conditions',
        'created_by',
        'published_at',
        'closed_at',
        'awarded_quotation_id',
    ];

    protected $casts = [
        'quotation_deadline' => 'date',
        'expected_delivery_date' => 'date',
        'published_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_AWAITING_QUOTATIONS = 'awaiting_quotations';

    public const STATUS_UNDER_EVALUATION = 'under_evaluation';

    public const STATUS_AWARDED = 'awarded';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    public const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_AWAITING_QUOTATIONS,
        self::STATUS_UNDER_EVALUATION,
        self::STATUS_AWARDED,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class, 'rfq_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(RfqInvitation::class, 'rfq_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'rfq_id');
    }

    public function awardedQuotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'awarded_quotation_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'rfq_id');
    }

    public function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "RFQ-{$year}{$month}-";

        $latest = static::where('rfq_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('rfq_number');

        $sequence = 1;
        if ($latest) {
            $sequence = ((int) substr($latest, strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function isPublished(): bool
    {
        return in_array($this->status, [
            self::STATUS_PUBLISHED,
            self::STATUS_AWAITING_QUOTATIONS,
            self::STATUS_UNDER_EVALUATION,
        ], true);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isAwarded(): bool
    {
        return $this->status === self::STATUS_AWARDED;
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_CLOSED, self::STATUS_CANCELLED], true);
    }

    public function hasQuotationDeadlinePassed(): bool
    {
        return $this->quotation_deadline->isPast();
    }
}
