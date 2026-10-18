<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TahfidzAssessmentRubric extends Model
{
    protected $table = 'tahfidz_assessment_rubrics';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'work_unit_id', 'name', 'components', 'kkm', 'is_active',
    ];

    protected $casts = [
        'components' => 'array',
        'kkm' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }
}
