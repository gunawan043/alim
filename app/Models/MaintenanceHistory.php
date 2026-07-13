<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MaintenanceHistory extends Model
{
    use HasFactory;

    protected $table = 'maintenance_histories';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'asset_id',
        'maintenance_log_id',
        'maintenance_schedule_id',
        'maintenance_type',
        'performed_date',
        'performed_by_name',
        'performed_by_user_id',
        'condition_before',
        'condition_after',
        'work_description',
        'cost',
        'next_due_date',
    ];

    protected $casts = [
        'performed_date' => 'date',
        'next_due_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
