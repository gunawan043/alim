<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WasteSource extends Model
{
    protected $table = 'waste_sources';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = ['name', 'source_type', 'work_unit_id', 'building_id', 'is_active', 'notes', 'created_by'];
    protected $casts = ['is_active' => 'boolean'];

    public const TYPE_OPTIONS = ['dapur', 'asrama', 'kelas', 'kantor', 'uks', 'masjid', 'kantin', 'taman', 'lainnya'];

    public function building(): BelongsTo { return $this->belongsTo(AssetBuilding::class, 'building_id'); }
    public function workUnit(): BelongsTo { return $this->belongsTo(WorkUnit::class, 'work_unit_id'); }
    public function logs(): HasMany { return $this->hasMany(WasteLog::class, 'source_id'); }
}
