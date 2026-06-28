<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TujuanPembelajaran extends Model
{
    protected $table = 'tujuan_pembelajaran';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static $bootDefault;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'id',
        'school_id',
        'subject_id',
        'kd',
        'tp',
        'indicator_code',
        'description',
        'cognitive_level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function soalOptions(): HasMany
    {
        return $this->hasMany(SoilOption::class, 'tp_id', 'id');
    }
}
