<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CleaningProgram extends Model
{
    protected $table = 'cleaning_programs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'title', 'area_id', 'building_id', 'category', 'frequency', 'months',
        'standard', 'method', 'assigned_to', 'is_active', 'notes', 'created_by',
    ];

    protected $casts = ['months' => 'array', 'is_active' => 'boolean'];

    public const CATEGORY_OPTIONS = [
        'kipas', 'gorong_gorong', 'batu_sikat', 'kanopi', 'plang_nama',
        'taman', 'kolam', 'pemangkasan', 'lainnya',
    ];

    public function area(): BelongsTo { return $this->belongsTo(CleaningArea::class, 'area_id'); }
    public function building(): BelongsTo { return $this->belongsTo(AssetBuilding::class, 'building_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
