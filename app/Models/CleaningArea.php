<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CleaningArea extends Model
{
    protected $table = 'cleaning_areas';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'work_unit_id', 'school_id', 'building_id', 'room_id', 'name',
        'area_type', 'location_notes', 'coordinator_id', 'is_active', 'created_by',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public const TYPE_OPTIONS = [
        'ruang_kelas', 'asrama', 'kantor', 'masjid', 'kamar_mandi', 'taman',
        'jalan', 'gorong_gorong', 'kantin', 'dapur', 'uks', 'lainnya',
    ];

    public function building(): BelongsTo { return $this->belongsTo(AssetBuilding::class, 'building_id'); }
    public function room(): BelongsTo { return $this->belongsTo(AssetRoom::class, 'room_id'); }
    public function workUnit(): BelongsTo { return $this->belongsTo(WorkUnit::class, 'work_unit_id'); }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function coordinator(): BelongsTo { return $this->belongsTo(User::class, 'coordinator_id'); }
    public function tasks(): HasMany { return $this->hasMany(CleaningTask::class, 'area_id')->orderBy('sort_order'); }
    public function assignments(): HasMany { return $this->hasMany(CleaningAssignment::class, 'area_id'); }
    public function reports(): HasMany { return $this->hasMany(CleaningReport::class, 'area_id'); }
    public function activeAssignments(): HasMany { return $this->assignments()->where('is_active', true); }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
