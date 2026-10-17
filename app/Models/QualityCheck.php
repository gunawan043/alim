<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityCheck extends Model
{
    use HasFactory;

    protected $table = 'quality_checks';

    protected $fillable = [
        'qc_number',
        'purchase_order_id',
        'goods_receipt_id',
        'status',
        'inspection_date',
        'inspector_id',
        'inspector_name',
        'inspection_criteria',
        'inspection_results',
        'sample_size',
        'passed_quantity',
        'failed_quantity',
        'pass_rate',
        'failure_reasons',
        'recommendations',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'inspection_criteria' => 'array',
        'inspection_results' => 'array',
        'pass_rate' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_PASSED = 'passed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PARTIALLY_PASSED = 'partially_passed';

    public const STATUS_CANCELLED = 'cancelled';

    public const ALLOWED_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_PASSED,
        self::STATUS_FAILED,
        self::STATUS_PARTIALLY_PASSED,
        self::STATUS_CANCELLED,
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "QC-{$year}{$month}-";

        $latest = static::where('qc_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('qc_number');

        $sequence = 1;
        if ($latest) {
            $sequence = ((int) substr($latest, strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function isInspectionComplete(): bool
    {
        return in_array($this->status, [
            self::STATUS_PASSED,
            self::STATUS_FAILED,
            self::STATUS_PARTIALLY_PASSED,
        ], true);
    }

    public function recalculatePassRate(): self
    {
        $sample = (int) ($this->sample_size ?? 0);
        $passed = (int) ($this->passed_quantity ?? 0);

        $this->pass_rate = $sample > 0 ? round(($passed / $sample) * 100, 2) : 0;

        return $this;
    }
}
