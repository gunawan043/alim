<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Kepesertaan asuransi/BPJS seorang santri.
 */
class UksStudentInsurance extends Model
{
    protected $table = 'uks_student_insurances';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id', 'provider', 'card_number', 'class_type',
        'valid_from', 'valid_until', 'status', 'notes',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public const STATUS_ACTIVE = 'aktif';

    public const STATUS_INACTIVE = 'nonaktif';

    public const STATUS_OPTIONS = [
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_INACTIVE => 'Nonaktif',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    public function statusLabel(): string
    {
        if ($this->isExpired()) {
            return 'Kedaluwarsa';
        }

        return self::STATUS_OPTIONS[$this->status] ?? $this->status;
    }
}
