<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    protected $fillable = ['code', 'name', 'symbol', 'category'];

    public function spareparts(): HasMany
    {
        return $this->hasMany(Sparepart::class, 'unit_id');
    }
}