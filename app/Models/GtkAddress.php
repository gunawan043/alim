<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GtkAddress extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'gtk_profile_id',
        'type',
        'jalan',
        'rt_rw',
        'dusun',
        'desa',
        'kode_pos',
        'kecamatan',
        'kab_kota',
        'provinsi',
        'province_code',
        'city_code',
        'district_code',
        'village_code',
    ];

    protected $casts = [
        'id' => 'string',
        'gtk_profile_id' => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function gtkProfile()
    {
        return $this->belongsTo(GtkProfile::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_code', 'code');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeKtp($query)
    {
        return $query->where('type', 'ktp');
    }

    public function scopeDomisili($query)
    {
        return $query->where('type', 'domisili');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->jalan,
            $this->rt_rw ? 'RT/RW '.$this->rt_rw : null,
            $this->dusun ? 'Dusun '.$this->dusun : null,
            $this->desa,
            $this->kecamatan ? 'Kec. '.$this->kecamatan : null,
            $this->kab_kota,
            $this->provinsi ? 'Prov. '.$this->provinsi : null,
            $this->kode_pos ? 'Kode Pos: '.$this->kode_pos : null,
        ]);

        return implode(', ', $parts);
    }

    public function getMaskedAddressAttribute(): array
    {
        return [
            'jalan' => preg_replace('/\b(\w+)\b/', '***', $this->jalan ?? ''),
            'desa' => $this->desa,
            'kecamatan' => $this->kecamatan,
            'kab_kota' => $this->kab_kota,
            'provinsi' => $this->provinsi,
        ];
    }
}
