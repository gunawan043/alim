<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegulationCategory extends Model
{
    use HasFactory;

    protected $table = 'regulation_categories';

    protected $fillable = [
        'name',
        'description',
    ];

    public function regulations(): HasMany
    {
        return $this->hasMany(BoardingRegulation::class, 'category_id');
    }
}