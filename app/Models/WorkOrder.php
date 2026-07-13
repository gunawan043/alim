<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WorkOrder extends Model
{
    use HasFactory;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'work_orders';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->id = $m->id ?? (string) Str::uuid();
            $m->order_number = $m->generateOrderNumber();
        });
    }

    protected $fillable = [
        'id', 'sync_token',
        'repair_request_id',
        'asset_id',
        'assignee_id',
        'type',
        'scope_of_work',
        'scheduled_date',
        'actual_start',
        'actual_end',
        'status',
        'completion_notes',
        'total_cost',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'total_cost' => 'decimal:2',
    ];

    public function repairRequest()
    {
        return $this->belongsTo(RepairRequest::class, 'repair_request_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function progressSteps()
    {
        return $this->hasMany(WorkOrderProgress::class, 'work_order_id');
    }

    public function sparePartUsages()
    {
        return $this->hasMany(SparePartUsage::class, 'work_order_id');
    }

    public function costHistories()
    {
        return $this->hasMany(RepairCostHistory::class, 'work_order_id');
    }

    protected function generateOrderNumber(): string
    {
        $year = now()->year;
        $seq = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('WO-%s-%04d', $year, $seq);
    }
}
