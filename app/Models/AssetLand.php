<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssetLand extends Model
{
    use HasFactory;

    protected $table = 'asset_lands';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'work_unit_id', 'school_id', 'land_name', 'certificate_number',
        'certificate_type', 'land_area', 'address', 'province_code', 'city_code',
        'district_code', 'village_code', 'latitude', 'longitude',
        'acquisition_year', 'acquisition_price', 'acquisition_source',
        'land_use', 'status', 'document_path', 'notes', 'created_by',
    ];

    protected $casts = [
        'land_area' => 'decimal:2',
        'acquisition_price' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:11',
    ];

    // RELATIONSHIPS
    public function school()    { return $this->belongsTo(School::class); }
    public function workUnit() { return $this->belongsTo(WorkUnit::class); }
    public function buildings() { return $this->hasMany(AssetBuilding::class, 'land_id'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
}
