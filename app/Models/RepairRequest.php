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
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'labor_cost' => 'decimal:2',
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
