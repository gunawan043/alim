<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GradeLevel extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = ['school_id', 'level', 'name', 'code', 'is_active'];

    protected $casts = [
        'level'     => 'integer',
        'is_active' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studyGroups(): HasMany
    {
        return $this->hasMany(StudyGroup::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(GradeLevelSubject::class);
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($q)   { return $q->where('is_active', true); }
    public function scopeForSchool($q, $sid) { return $q->where('school_id', $sid); }
}
