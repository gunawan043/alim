<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillCategoryMapping extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'skill_category_mappings';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? \Illuminate\Support\Str::uuid());
    }

    protected $fillable = [
        'id', 'category_slug', 'required_skill_slug',
    ];

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'category_slug', 'slug');
    }
}