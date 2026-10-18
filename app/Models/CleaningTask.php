<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CleaningTask extends Model
{
    protected $table = 'cleaning_tasks';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'area_id', 'title', 'standard', 'method', 'frequency',
        'sort_order', 'is_active', 'created_by',
    ];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public const FREQUENCY_OPTIONS = ['harian', 'mingguan', 'bulanan', 'triwulan', 'semester', 'tahunan'];

    public function area(): BelongsTo { return $this->belongsTo(CleaningArea::class, 'area_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
