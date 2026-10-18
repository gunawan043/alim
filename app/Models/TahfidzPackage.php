<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TahfidzPackage extends Model
{
    protected $table = 'tahfidz_packages';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'work_unit_id', 'name', 'code', 'description', 'total_target_juz',
        'total_target_halaman', 'lines_per_page', 'total_pages',
        'duration_semesters', 'is_active',
    ];

    protected $casts = [
        'total_target_juz' => 'integer',
        'total_target_halaman' => 'integer',
        'lines_per_page' => 'integer',
        'total_pages' => 'integer',
        'duration_semesters' => 'integer',
        'is_active' => 'boolean',
    ];

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function curriculums(): HasMany
    {
        return $this->hasMany(TahfidzCurriculum::class, 'tahfidz_package_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(TahfidzGroup::class, 'tahfidz_package_id');
    }
}
