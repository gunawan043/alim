<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TodoComment extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'todo_id',
        'user_id',
        'comment',
        'parent_comment_id',
        'is_edited',
        'edited_at',
    ];

    protected $casts = [
        'is_edited' => 'integer',
        'edited_at' => 'datetime',
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

    public function parentComment(): BelongsTo
    {
        return $this->belongsTo(TodoComment::class, 'parent_comment_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TodoComment::class, 'parent_comment_id')->orderBy('created_at');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeRootComments($query)
    {
        return $query->whereNull('parent_comment_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    // ─── Helpers ────────────────────────────────────────────────

    public function markAsEdited(): void
    {
        $this->update([
            'is_edited' => 1,
            'edited_at' => now(),
        ]);
    }
}
