<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RepairRequest extends Model
{
    use HasFactory;

    protected $table = 'repair_requests';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->id = $m->id ?? (string) Str::uuid();
            $m->request_number = $m->generateRequestNumber();
        });
    }

    protected $fillable = [
        'asset_id',
        'reported_by',
        'assigned_to',
        'title',
        'description',
        'priority',
        'status',
        'verification_notes',
        'reviewer_id',
        'verified_at',
        'approved_at',
        'started_at',
        'completed_at',
        'closed_at',
        'result_description',
        'labor_cost',
        'rejected_reason',
        'recommended_action',
        'feedback_for_reporter',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'labor_cost' => 'decimal:2',
    ];

    public const STATUS_VERIFICATION_PENDING = 'verification_pending';
    public const STATUS_VERIFICATION_IN_PROGRESS = 'verification_in_progress';
    public const STATUS_ADDITIONAL_INFO = 'additional_info';
    public const STATUS_APPROVAL_PENDING = 'approval_pending';
    public const STATUS_EXECUTION_PENDING = 'execution_pending';
    public const STATUS_STARTED = 'started';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_STOPPED = 'stopped';
    public const STATUS_VERIFICATION_REJECTED = 'verification_rejected';
    public const STATUS_APPROVAL_REJECTED = 'approval_rejected';
    public const STATUS_CLOSED = 'closed';

    public const RECOMMENDATION_APPROVED = 'approved';
    public const RECOMMENDATION_REJECTED = 'rejected';

    public const ALLOWED_STATUSES = [
        self::STATUS_VERIFICATION_PENDING,
        self::STATUS_VERIFICATION_IN_PROGRESS,
        self::STATUS_ADDITIONAL_INFO,
        self::STATUS_APPROVAL_PENDING,
        self::STATUS_EXECUTION_PENDING,
        self::STATUS_STARTED,
        self::STATUS_COMPLETED,
        self::STATUS_STOPPED,
        self::STATUS_VERIFICATION_REJECTED,
        self::STATUS_APPROVAL_REJECTED,
        self::STATUS_CLOSED,
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'repair_request_id');
    }

    public function costHistories()
    {
        return $this->hasMany(RepairCostHistory::class, 'repair_request_id');
    }

    protected function generateRequestNumber(): string
    {
        $year = now()->year;
        $seq = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('RPR-%s-%04d', $year, $seq);
    }
}
