<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Todo extends Model
{
    use LogsDeletion;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'todo_list_id',
        'owner_id',
        'created_by',
        'delegated_by',
        'delegated_at',
        'title',
        'description',
        'priority',
        'tags',
        'due_date',
        'due_time',
        'reminder_at',
        'started_at',
        'completed_at',
        'status',
        'progress_percent',
        'is_pinned',
        'is_private',
        'related_type',
        'related_id',
        'work_unit_id',
        'school_id',
        'academic_year_id',
        'sort_order',
        'cancelled_reason',
        'created_at_timezone',
    ];

    protected $casts = [
        'due_date' => 'date',
        'reminder_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'delegated_at' => 'datetime',
        'progress_percent' => 'integer',
        'is_pinned' => 'integer',
        'is_private' => 'integer',
        'sort_order' => 'integer',
        'created_at_timezone' => 'string',
    ];

    protected $appends = ['is_overdue', 'is_due_soon'];

    public const STATUSES = [
        'belum_mulai',
        'sedang_berjalan',
        'selesai',
        'dibatalkan',
        'ditunda',
    ];

    public const PRIORITIES = [
        'rendah',
        'sedang',
        'tinggi',
        'mendesak',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
            if (empty($model->owner_id)) {
                $model->owner_id = auth()->id();
            }
        });

        static::created(function ($todo) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'TODO_CREATED',
                'table_name' => 'todos',
                'record_id' => $todo->id,
            ]);
        });

        static::updated(function ($todo) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'TODO_UPDATED',
                'table_name' => 'todos',
                'record_id' => $todo->id,
            ]);
        });

        static::deleted(function ($todo) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'TODO_DELETED',
                'table_name' => 'todos',
                'record_id' => $todo->id,
            ]);
        });
    }

    // ─── Accessors for badges ─────────────────────────────────────

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'belum_mulai' => 'bg-secondary-subtle text-secondary text-uppercase',
            'sedang_berjalan' => 'bg-primary-subtle text-primary text-uppercase',
            'selesai' => 'bg-success-subtle text-success text-uppercase',
            'ditunda' => 'bg-warning-subtle text-warning text-uppercase',
            'dibatalkan' => 'bg-dark-subtle text-dark text-uppercase',
            default => 'bg-secondary-subtle text-secondary text-uppercase',
        };
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            'rendah' => 'bg-success-subtle text-success text-uppercase',
            'sedang' => 'bg-info-subtle text-info text-uppercase',
            'tinggi' => 'bg-warning-subtle text-warning text-uppercase',
            'mendesak' => 'bg-danger-subtle text-danger text-uppercase',
            default => 'bg-secondary-subtle text-secondary text-uppercase',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'belum_mulai' => 'Belum Mulai',
            'sedang_berjalan' => 'Berjalan',
            'selesai' => 'Selesai',
            'ditunda' => 'Ditunda',
            'dibatalkan' => 'Dibatalkan',
            default => $this->status ?? '',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'rendah' => 'Rendah',
            'sedang' => 'Sedang',
            'tinggi' => 'Tinggi',
            'mendesak' => 'Mendesak',
            default => $this->priority ?? '',
        };
    }

    // ─── Relations ───────────────────────────────────────────────

    public function todoList(): BelongsTo
    {
        return $this->belongsTo(TodoList::class, 'todo_list_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function delegatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_by');
    }

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(TodoSubtask::class, 'todo_id')->ordered();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TodoComment::class, 'todo_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TodoAttachment::class, 'todo_id');
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(TodoWatcher::class, 'todo_id');
    }

    public function related(): MorphTo
    {
        return $this->morphTo('related', 'related_type', 'related_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeOwnedBy($query, string $userId)
    {
        return $query->where('owner_id', $userId);
    }

    public function scopeDelegatedBy($query, string $userId)
    {
        return $query->where('delegated_by', $userId);
    }

    public function scopeWatchedBy($query, string $userId)
    {
        return $query->whereHas('watchers', fn ($q) => $q->where('todo_watchers.user_id', $userId));
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByList($query, ?string $listId)
    {
        if ($listId) {
            return $query->where('todo_list_id', $listId);
        }

        return $query;
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['selesai', 'dibatalkan']);
    }

    public function scopeDueSoon($query, int $days = 3)
    {
        return $query->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->whereNotIn('status', ['selesai', 'dibatalkan']);
    }

    public function scopeNeedsReminder($query)
    {
        return $query->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', now())
            ->whereNotIn('status', ['selesai', 'dibatalkan']);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', 1);
    }

    public function scopeNotPrivate($query)
    {
        return $query->where('is_private', 0);
    }

    public function scopePublicForUser($query, string $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('is_private', 0)
                ->orWhere('owner_id', $userId)
                ->orWhere('created_by', $userId)
                ->orWhere('delegated_by', $userId);
        });
    }

    public function scopeWithFilters($query, array $filters = [])
    {
        $query = $query->with(['owner', 'delegatedByUser', 'subtasks']);

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        if (! empty($filters['list_id'])) {
            $query->byList($filters['list_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_pinned'])) {
            $query->pinned();
        }

        // Default sort: pinned first, then by sort_order
        $sortBy = $filters['sort_by'] ?? 'sort_order';
        $sortDir = $filters['sort_dir'] ?? 'asc';

        if ($sortBy === 'due_date') {
            $query->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('due_date', $sortDir);
        } else {
            $query->orderBy('is_pinned', 'desc')
                ->orderBy($sortBy, $sortDir);
        }

        return $query;
    }

    // ─── Accessors ───────────────────────────────────────────────

    public function getIsOverdueAttribute(): bool
    {
        if (! $this->due_date) {
            return false;
        }

        return $this->due_date->lt(now()->toDateString())
            && ! in_array($this->status, ['selesai', 'dibatalkan']);
    }

    public function getIsDueSoonAttribute(): bool
    {
        if (! $this->due_date) {
            return false;
        }

        return $this->due_date->between(
            now()->toDateString(),
            now()->addDays(3)->toDateString()
        ) && ! in_array($this->status, ['selesai', 'dibatalkan']);
    }

    public function getSubtaskProgressAttribute(): int
    {
        if ($this->subtasks->isEmpty()) {
            return $this->progress_percent ?? 0;
        }
        $total = $this->subtasks->count();
        $done = $this->subtasks->where('is_completed', 1)->count();

        return $total > 0 ? (int) round(($done / $total) * 100) : 0;
    }

    public function getDelegationBadgeAttribute(): ?string
    {
        if (! $this->delegated_by) {
            return null;
        }

        return $this->delegatedByUser?->name;
    }

    // ─── Helpers ────────────────────────────────────────────────

    public function recalculateProgress(): void
    {
        $subtasks = $this->subtasks()->get();

        if ($subtasks->isEmpty()) {
            return;
        }

        $total = $subtasks->count();
        $done = $subtasks->where('is_completed', 1)->count();
        $percent = (int) round(($done / $total) * 100);

        $this->updateQuietly(['progress_percent' => $percent]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'selesai',
            'completed_at' => now(),
            'progress_percent' => 100,
        ]);
    }

    public function assignWatcher(string $userId, ?string $addedBy = null): self
    {
        if (! TodoWatcher::isWatching($this->id, $userId)) {
            TodoWatcher::create([
                'todo_id' => $this->id,
                'user_id' => $userId,
                'added_by' => $addedBy ?? auth()->id(),
            ]);
        }

        return $this;
    }
}
