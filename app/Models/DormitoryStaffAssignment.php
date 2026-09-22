<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DormitoryStaffAssignment extends Model
{
    use SoftDeletes;

    protected $table = 'dormitory_staff_assignments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'user_id',
        'dormitory_id',
        'assigned_by_id',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class, 'dormitory_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function scopeActive($query)
    {
        $today = now()->toDateString();

        return $query->where('status', 'active')
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                    ->orWhere(function ($q2) use ($today) {
                        $q2->whereNotNull('end_date')
                            ->whereRaw('DATE(end_date) >= ?', [$today]);
                    });
            })
            ->whereRaw('DATE(start_date) <= ?', [$today]);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDormitory($query, string $dormitoryId)
    {
        return $query->where('dormitory_id', $dormitoryId);
    }
}
