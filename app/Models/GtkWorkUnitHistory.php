<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GtkWorkUnitHistory extends Model
{
    use HasFactory;

    protected $table = 'gtk_work_unit_histories';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'from_work_unit_id',
        'to_work_unit_id',
        'jabatan',
        'action',
        'reason',
        'performed_by',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'from_work_unit_id' => 'string',
        'to_work_unit_id' => 'string',
        'performed_by' => 'string',
    ];

    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fromWorkUnit()
    {
        return $this->belongsTo(WorkUnit::class, 'from_work_unit_id');
    }

    public function toWorkUnit()
    {
        return $this->belongsTo(WorkUnit::class, 'to_work_unit_id');
    }

    public function performedByUser()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // SCOPES
    public function scopeByUser($query, $userId)
    {
        return $this->where('user_id', $userId);
    }

    public function scopeByWorkUnit($query, $workUnitId)
    {
        return $query->where('to_work_unit_id', $workUnitId)
                    ->orWhere('from_work_unit_id', $workUnitId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ACCESSORS
    public function getActionTextAttribute()
    {
        $actions = [
            'ASSIGN' => 'Penempatan',
            'TRANSFER' => 'Mutasi',
            'REMOVE' => 'Penghapusan',
        ];

        return $actions[$this->action] ?? $this->action;
    }
}