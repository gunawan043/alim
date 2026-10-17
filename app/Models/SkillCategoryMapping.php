<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SkillCategoryMapping extends Model
{
    use SoftDeletes;

    protected $table = 'skill_category_mappings';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? Str::uuid());
    }

    protected $fillable = [
        'id', 'category_slug', 'required_skill_slug',
    ];

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'category_slug', 'slug');
    }
}
