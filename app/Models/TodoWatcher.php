<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TodoWatcher extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'todo_id',
        'user_id',
        'added_by',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    // ─── Helpers ────────────────────────────────────────────────

    /**
     * Check if a user is already watching a todo.
     */
    public static function isWatching(string $todoId, string $userId): bool
    {
        return static::where('todo_id', $todoId)
            ->where('user_id', $userId)
            ->exists();
    }
}
