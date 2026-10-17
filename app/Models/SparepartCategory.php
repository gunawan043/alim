<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SparepartCategory extends Model
{
    use HasFactory;

    protected $table = 'sparepart_categories';

    protected $fillable = ['name', 'slug', 'parent_id', 'description', 'is_active'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SparepartCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(SparepartCategory::class, 'parent_id');
    }

    public function spareparts(): HasMany
    {
        return $this->hasMany(Sparepart::class, 'category_id');
    }
}
