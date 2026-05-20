<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssetMaintenanceLog extends Model
{
    use HasFactory;

    protected $table = 'asset_maintenance_logs';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'schedule_id',
        'work_unit_id',
        'school_id',
        'asset_id',
        'building_id',
        'room_id',
        'maintenance_type',
        'maintenance_date',
        'performed_by',
        'vendor_name',
        'actual_cost',
        'condition_before',
        'condition_after',
        'work_description',
        'parts_replaced',
        'next_action_needed',
        'photo_path',
        'document_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'actual_cost' => 'decimal:2',
    ];

    const CONDITION_OPTIONS = ['baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat'];

    // RELATIONSHIPS
    public function schedule()
    {
        return $this->belongsTo(AssetMaintenanceSchedule::class, 'schedule_id');
    }

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function building()
    {
        return $this->belongsTo(AssetBuilding::class, 'building_id');
    }

    public function room()
    {
        return $this->belongsTo(AssetRoom::class, 'room_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
