<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssetCategory extends Model
{
    use HasFactory;

    protected $table = 'asset_categories';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'parent_id', 'code', 'name', 'asset_type',
        'depreciation_years', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'depreciation_years' => 'integer',
    ];

    const ASSET_TYPE_OPTIONS = ['tidak_bergerak', 'bergerak', 'habis_pakai'];

    // RELATIONSHIPS
    public function parent()
    {
        return $this->belongsTo(AssetCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AssetCategory::class, 'parent_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'asset_category_id');
    }

    // SCOPES
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
