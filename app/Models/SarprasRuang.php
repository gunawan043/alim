<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class SarprasRuang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sarpras_ruangs';
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
        'school_id',
        'nama',
        'kode',
        'kategori',
        'lokasi',
        'lantai',
        'panjang_m',
        'lebar_m',
        'luas_m2',
        'kapasitas',
        'kondisi',
        'keterangan',
        'is_ruang_kelas',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'school_id' => 'string',
        'lantai' => 'integer',
        'kapasitas' => 'integer',
        'is_ruang_kelas' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const KATEGORI_OPTIONS = [
        'Ruang Kelas',
        'Ruang Perpustakaan',
        'Ruang Laboratorium',
        'Ruang UKS',
        'Ruang Guru',
        'Ruang Tata Usaha',
        'Ruang Head Master',
        'Ruang Meeting',
        'Ruang Ibadah',
        'Ruang OSIS',
        'Ruang Multimedia',
        'Ruang Kantin',
        'Ruang Musholla',
        'Ruang Gudang',
        'Ruang WC/Toilet',
        'Ruang Parkir',
        'Ruang Olahraga',
        'Ruang Seni',
        'Ruang Keterampilan',
        'Lahan Terbuka',
        'Lainnya',
    ];

    const KONDISI_OPTIONS = ['Baik', 'Sedang', 'Rusak Ringan', 'Rusak Berat'];

    // RELATIONSHIPS
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function sarprasSaranas()
    {
        return $this->hasMany(SarprasSarana::class, 'sarpras_ruang_id');
    }

    // SCOPES
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    public function scopeRuangKelas($query)
    {
        return $query->where('is_ruang_kelas', true);
    }
}
