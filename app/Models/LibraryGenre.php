<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryGenre extends Model
{
    use HasUuids;

    protected $table = 'library_genres';

    protected $fillable = ['school_id', 'code', 'name', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function titles(): HasMany { return $this->hasMany(LibraryTitle::class, 'genre_id'); }
}
