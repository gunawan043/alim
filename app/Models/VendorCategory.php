<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorCategory extends Model
{
    use HasFactory;

    protected $table = 'vendor_categories';

    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class, 'category_id');
    }
}
