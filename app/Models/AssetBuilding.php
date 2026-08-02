<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssetBuilding extends Model
{
    use HasFactory;

    protected $table = 'asset_buildings';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'work_unit_id',
        'school_id',
        'land_id',
        'building_code',
        'building_name',
        'building_type',
        'total_floors',
        'building_area',
        'build_year',
        'renovation_year',
        'structure_condition',
        'ownership_status',
        'imb_number',
        'imb_date',
        'total_rooms',
        'notes',
        'photo_path',
        'document_path',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'imb_date' => 'date',
        'is_active' => 'boolean',
        'building_area' => 'decimal:2',
    ];

    const BUILDING_TYPE_OPTIONS = [
        'kelas', 'kantor', 'laboratorium', 'perpustakaan',
        'masjid', 'mushola', 'asrama', 'aula', 'kantin',
        'uks', 'koperasi', 'gudang', 'toilet', 'lapangan', 'lainnya',
    ];

    const CONDITION_OPTIONS = ['baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat'];

    const OWNERSHIP_OPTIONS = ['milik_sendiri', 'sewa', 'pinjam', 'kerjasama'];

    // RELATIONSHIPS
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function land()
    {
        return $this->belongsTo(AssetLand::class, 'land_id');
    }

    public function rooms()
    {
        return $this->hasMany(AssetRoom::class, 'building_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ACCESSORS
    protected function buildingName(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($v) => $v ? ucwords(strtolower($v)) : $v,
        );
    }
}
