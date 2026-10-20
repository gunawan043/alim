<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CurriculumProfileMappingLog extends Model
{
    protected $table = 'curriculum_profile_mapping_logs';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'batch_id',
        'school_id',
        'academic_year_id',
        'old_profile_id',
        'new_profile_id',
        'old_kokurikuler_type',
        'new_kokurikuler_type',
        'old_setting_exists',
        'changed_by',
        'rolled_back_at',
        'rolled_back_by',
    ];

    protected $casts = [
        'old_setting_exists' => 'boolean',
        'rolled_back_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function oldProfile(): BelongsTo
    {
        return $this->belongsTo(CurriculumProfile::class, 'old_profile_id');
    }

    public function newProfile(): BelongsTo
    {
        return $this->belongsTo(CurriculumProfile::class, 'new_profile_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function rollbacker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rolled_back_by');
    }
}
