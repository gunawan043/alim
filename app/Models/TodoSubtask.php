<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TodoSubtask extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'todo_id',
        'title',
        'is_completed',
        'completed_at',
        'completed_by',
        'sort_order',
    ];

    protected $casts = [
        'is_completed' => 'integer',
        'completed_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // ─── Relations ───────────────────────────────────────────────

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', 1);
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', 0);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // ─── Accessors ───────────────────────────────────────────────

    public function getIsDoneAttribute(): bool
    {
        return (bool) $this->is_completed;
    }
}
