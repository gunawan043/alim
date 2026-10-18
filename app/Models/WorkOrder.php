<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WorkOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'work_orders';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->uuid = $m->uuid ?: (string) Str::uuid();
            $m->order_number = $m->order_number ?: $m->generateOrderNumber();
        });
    }

    protected $fillable = [
        'uuid', 'sync_token',
        'repair_request_id',
        'maintenance_schedule_id',
        'asset_id',
        'room_id',
        'building_id',
        'assignee_id',
        'created_by',
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

    public function maintenanceSchedule()
    {
        return $this->belongsTo(AssetMaintenanceSchedule::class, 'maintenance_schedule_id');
    }

    public function room()
    {
        return $this->belongsTo(AssetRoom::class, 'room_id');
    }

    public function building()
    {
        return $this->belongsTo(AssetBuilding::class, 'building_id');
    }

    public function getTargetNameAttribute(): string
    {
        return $this->asset?->asset_name
            ?? $this->room?->room_name
            ?? $this->building?->building_name
            ?? '—';
    }

    public function getTargetLocationAttribute(): string
    {
        if ($this->asset) {
            return trim(($this->asset->room?->building?->building_name ? $this->asset->room->building->building_name.' — ' : '').($this->asset->room?->room_name ?? '')) ?: '—';
        }

        return $this->room?->building?->building_name
            ?? $this->room?->room_name
            ?? $this->building?->building_name
            ?? '—';
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function assignedTo()
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
