<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WorkUnitSarprasOfficer extends Model
{
    use HasFactory;

    protected $table = 'work_unit_sarpras_officers';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = $model->id ?: (string) Str::uuid();
        });
    }

    protected $fillable = [
        'work_unit_id',
        'user_id',
        'is_primary',
        'is_active',
        'assigned_by',
        'assigned_at',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
