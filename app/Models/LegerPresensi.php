<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LegerPresensi extends Model
{
    protected $table = 'admin_leger_presensi';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'study_group_id',
        'academic_year_id',
        'semester',
        'student_id',
        's',
        'i',
        'a',
    ];

    protected $casts = [
        's' => 'integer',
        'i' => 'integer',
        'a' => 'integer',
    ];
}
