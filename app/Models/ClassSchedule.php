<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSchedule extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) \Illuminate\Support\Str::uuid());
    }

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'name',
        'status',
        'generated_at',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ClassScheduleSlot::class, 'schedule_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeDraft($q)
    {
        return $q->where('status', 'draft');
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }
}
