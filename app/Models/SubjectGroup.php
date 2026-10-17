<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Rumpun Mata Pelajaran — entitas resmi lembaga.
 * Keanggotaan mapel via `subjects.subject_group_id`; koordinator via tugas tambahan.
 */
class SubjectGroup extends Model
{
    protected $table = 'subject_groups';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'code',
        'name',
        'coordinator_task_name',
        'patterns',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'patterns' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'subject_group_id');
    }

    /** Label pilihan untuk form. */
    public static function options(): array
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->all();
    }
}
