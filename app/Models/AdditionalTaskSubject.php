<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Mapel yang menempel pada satu tipe tugas tambahan (Guru Pendamping).
 */
class AdditionalTaskSubject extends Model
{
    protected $table = 'additional_task_subjects';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'additional_task_type_id',
        'subject_id',
        'urutan',
    ];

    protected $casts = [
        'urutan' => 'integer',
    ];

    public function taskType(): BelongsTo
    {
        return $this->belongsTo(AdditionalTaskType::class, 'additional_task_type_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
