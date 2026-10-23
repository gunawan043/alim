<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DormitoryPointCatalog extends Model
{
    protected $table = 'dormitory_point_catalogs';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['kind','code','name','category','severity','points_min','points_max','is_permanent','is_active','sort_order','description'];
    protected $casts = ['is_permanent'=>'boolean','is_active'=>'boolean','points_min'=>'integer','points_max'=>'integer','sort_order'=>'integer'];
    protected static function booted(): void { static::creating(fn (self $m) => $m->id = $m->id ?: (string) Str::uuid()); }
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeKind($q, string $kind) { return $q->where('kind', $kind); }
}
