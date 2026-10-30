<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BumpOrderCache extends Model
{
    use HasUuids;

    protected $table = 'bump_order_cache';

    protected $fillable = [
        'user_id', 'work_unit_id', 'school_id', 'external_reference', 'pos_order_number',
        'payment_method', 'status', 'payment_status', 'total', 'payload', 'last_synced_at',
    ];

    protected $casts = ['payload' => 'array', 'last_synced_at' => 'datetime', 'total' => 'decimal:2'];

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BumpOrderCacheLog::class, 'bump_order_cache_id')->latest();
    }
}
