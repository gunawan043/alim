<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Kegiatan malam asrama yang perlu pendampingan petugas.
 */
class DormitoryNightActivity extends Model
{
    protected $table = 'dormitory_night_activities';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'dormitory_id', 'academic_year_id', 'activity_date', 'name',
        'start_time', 'end_time', 'location', 'person_in_charge',
        'student_count', 'notes', 'status', 'created_by',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'student_count' => 'integer',
    ];

    public const STATUS_PLANNED = 'rencana';

    public const STATUS_DONE = 'selesai';

    public const STATUS_CANCELLED = 'batal';

    public const STATUS_OPTIONS = [
        self::STATUS_PLANNED => 'Rencana',
        self::STATUS_DONE => 'Selesai',
        self::STATUS_CANCELLED => 'Batal',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PLANNED => 'warning',
        self::STATUS_DONE => 'success',
        self::STATUS_CANCELLED => 'secondary',
    ];

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('activity_date', '>=', now()->toDateString());
    }

    public function statusLabel(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }
}
