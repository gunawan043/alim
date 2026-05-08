<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DormitoryActivityTemplate extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'dormitory_id',
        'session',
        'activity_items',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'activity_items' => 'array',
        'is_active'     => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getSessionTextAttribute(): string
    {
        return match ($this->session) {
            'subuh' => 'Subuh',
            'pagi'  => 'Pagi',
            'siang' => 'Siang',
            'sore'  => 'Sore',
            'isya'  => 'Isya',
            'malam' => 'Malam',
            default => ucfirst($this->session ?? ''),
        };
    }

    public function getItemCountAttribute(): int
    {
        return is_array($this->activity_items) ? count($this->activity_items) : 0;
    }
}
