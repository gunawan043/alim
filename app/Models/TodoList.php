<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TodoList extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'name',
        'color',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'integer',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class, 'todo_list_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDefaultList($query, string $userId)
    {
        return $query->where('user_id', $userId)->where('is_default', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Get the default list for a user, create one if it doesn't exist.
     */
    public static function getOrCreateDefault(string $userId): self
    {
        $list = static::defaultList($userId)->first();

        if (!$list) {
            $maxOrder = static::forUser($userId)->max('sort_order') ?? 0;
            $list = static::create([
                'user_id'    => $userId,
                'name'       => 'Todo Saya',
                'color'      => '#0ab39c',
                'is_default' => 1,
                'sort_order' => $maxOrder + 1,
            ]);
        }

        return $list;
    }
}
