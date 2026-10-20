<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryStaff extends Model
{
    use HasUuids;

    protected $table = 'library_staff';

    protected $fillable = ['library_unit_id', 'user_id', 'role', 'is_active', 'started_at', 'ended_at'];
    protected $casts = ['is_active' => 'boolean', 'started_at' => 'date', 'ended_at' => 'date'];

    public function unit(): BelongsTo { return $this->belongsTo(LibraryUnit::class, 'library_unit_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function scopeActive($query) { return $query->where('is_active', true); }
}
