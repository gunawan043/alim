<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DormitoryPointRule extends Model
{
    protected $table = 'dormitory_point_rules';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['name','min_points','max_points','action','requires_approval','notify_roles','is_active','sort_order'];
    protected $casts = ['min_points'=>'integer','max_points'=>'integer','requires_approval'=>'boolean','is_active'=>'boolean','notify_roles'=>'array','sort_order'=>'integer'];
    protected static function booted(): void { static::creating(fn (self $m) => $m->id = $m->id ?: (string) Str::uuid()); }
}
