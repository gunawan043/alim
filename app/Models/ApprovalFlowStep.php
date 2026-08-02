<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApprovalFlowStep extends Model
{
    use HasFactory;

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
        'approval_flow_id',
        'step_order',
        'role_name',
        'min_role_level',
    ];

    protected $casts = [
        'id' => 'string',
        'approval_flow_id' => 'string',
        'step_order' => 'integer',
        'min_role_level' => 'integer',
    ];

    // RELATIONSHIPS
    public function approvalFlow()
    {
        return $this->belongsTo(ApprovalFlow::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_name', 'name');
    }

    // SCOPES
    public function scopeByStepOrder($query, $order)
    {
        return $query->where('step_order', $order);
    }

    public function scopeByRole($query, $roleName)
    {
        return $query->where('role_name', $roleName);
    }
}
