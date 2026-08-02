<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssetRoom extends Model
{
    use HasFactory;

    protected $table = 'asset_rooms';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->id = $m->id ?? (string) Str::uuid();
            if (! $m->room_code) {
                $prefix = strtoupper(substr($m->room_type ?? 'kelas', 0, 3));
                $last = static::where('room_code', 'LIKE', "{$prefix}-%")
                    ->orderBy('room_code', 'desc')
                    ->value('room_code');
                $number = 1;
                if ($last && preg_match('/-(\d+)$/', $last, $match)) {
                    $number = intval($match[1]) + 1;
                }
                $m->room_code = $prefix.'-'.str_pad($number, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    protected $fillable = [
        'work_unit_id',
        'school_id',
        'building_id',
        'study_group_id',
        'room_code',
        'room_name',
        'floor',
        'room_type',
        'room_area',
        'capacity',
        'condition',
        'facilities',
        'is_bookable',
        'booking_requires_approval',
        'responsible_user_id',
        'photo_path',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'floor' => 'integer',
        'capacity' => 'integer',
        'room_area' => 'decimal:2',
        'is_bookable' => 'boolean',
        'booking_requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    const ROOM_TYPE_OPTIONS = [
        'kelas', 'laboratorium', 'perpustakaan', 'kantor',
        'aula', 'mushola', 'uks', 'kantin', 'gudang',
        'toilet', 'lapangan', 'lainnya',
    ];

    const CONDITION_OPTIONS = ['baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat'];

    // RELATIONSHIPS
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function building()
    {
        return $this->belongsTo(AssetBuilding::class, 'building_id');
    }

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function studyGroup()
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'room_id');
    }

    // SCOPES
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBookable($query)
    {
        return $query->where('is_bookable', true);
    }
}
