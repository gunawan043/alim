<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssetMaintenanceSchedule extends Model
{
    use HasFactory;

    protected $table = 'asset_maintenance_schedules';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'work_unit_id',
        'school_id',
        'asset_id',
        'building_id',
        'room_id',
        'maintenance_type',
        'frequency',
        'last_maintenance_date',
        'next_maintenance_date',
        'responsible_user_id',
        'vendor_name',
        'estimated_cost',
        'reminder_days_before',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    const FREQUENCY_OPTIONS = [
        'harian', 'mingguan', 'bulanan',
        'triwulan', 'semester', 'tahunan', 'sesuai_kebutuhan',
    ];

    // RELATIONSHIPS
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

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs()
    {
        return $this->hasMany(AssetMaintenanceLog::class, 'schedule_id');
    }

    // SCOPES
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueSoon($query, $days = 7)
    {
        return $query->where('is_active', true)
            ->whereDate('next_maintenance_date', '<=', now()->addDays($days));
    }

    public function scopeOverdue($query)
    {
        return $query->where('is_active', true)
            ->whereDate('next_maintenance_date', '<', now());
    }
}
