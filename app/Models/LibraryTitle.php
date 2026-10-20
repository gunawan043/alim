<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryTitle extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'library_titles';

    protected $fillable = [
        'school_id', 'genre_id', 'collection_type', 'title', 'author', 'publisher',
        'publication_year', 'isbn', 'description', 'cover_path', 'is_active',
    ];

    protected $casts = ['publication_year' => 'integer', 'is_active' => 'boolean'];

    public function genre(): BelongsTo { return $this->belongsTo(LibraryGenre::class, 'genre_id'); }
    public function items(): HasMany { return $this->hasMany(LibraryItem::class); }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
}
