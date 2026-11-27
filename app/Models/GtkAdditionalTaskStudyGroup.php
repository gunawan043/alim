<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;

/**
 * Pivot kelas untuk tugas tambahan Guru Pendamping.
 * Memakai UUID agar konsisten dengan tabel lain di aplikasi.
 */
class GtkAdditionalTaskStudyGroup extends Pivot
{
    protected $table = 'gtk_additional_task_study_groups';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }
}
