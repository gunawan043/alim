<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryUnit extends Model
{
    use HasUuids;

    protected $table = 'library_units';

    protected $fillable = [
        'work_unit_id', 'school_id', 'asset_room_id', 'code', 'name', 'gender_scope',
        'level_scope', 'location', 'is_active', 'notes',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function workUnit(): BelongsTo { return $this->belongsTo(WorkUnit::class); }
    public function room(): BelongsTo { return $this->belongsTo(AssetRoom::class, 'asset_room_id'); }
    public function staff(): HasMany { return $this->hasMany(LibraryStaff::class); }
    public function settings(): HasMany { return $this->hasMany(LibrarySetting::class); }
    public function items(): HasMany { return $this->hasMany(LibraryItem::class); }
    public function members(): HasMany { return $this->hasMany(LibraryMember::class); }
    public function loans(): HasMany { return $this->hasMany(LibraryLoan::class); }
    public function visits(): HasMany { return $this->hasMany(LibraryVisit::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
