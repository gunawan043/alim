<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WasteFacility extends Model
{
    protected $table = 'waste_facilities';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'name', 'facility_type', 'building_id', 'location', 'capacity_m3',
        'capacity_kg', 'condition', 'pic_user_id', 'is_active', 'notes', 'created_by',
    ];

    protected $casts = [
        'capacity_m3' => 'decimal:2',
        'capacity_kg' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public const TYPE_OPTIONS = [
        'tps' => 'TPS',
        'komposter' => 'Komposter',
        'bank_sampah' => 'Bank Sampah',
        'biodigester' => 'Biodigester',
        'incinerator' => 'Incinerator',
        'lainnya' => 'Lainnya',
    ];

    public const CONDITION_OPTIONS = ['baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat'];

    public function building(): BelongsTo
    {
        return $this->belongsTo(AssetBuilding::class, 'building_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WasteLog::class, 'facility_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
