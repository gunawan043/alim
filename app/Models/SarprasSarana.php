<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SarprasSarana extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sarpras_saranas';
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
        'sarpras_ruang_id',
        'nama',
        'kode',
        'jenis',
        'merek',
        'tipe',
        'no_seri',
        'jumlah',
        'satuan',
        'kondisi',
        'tanggal_perolehan',
        'harga_perolehan',
        'sumber_dana',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'sarpras_ruang_id' => 'string',
        'jumlah' => 'integer',
        'tanggal_perolehan' => 'date',
        'harga_perolehan' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const JENIS_OPTIONS = [
        'Barang Elektronik',
        'Furniture',
        'Alat Praktek',
        'Alat Olahraga',
        'Alat Musik',
        'Buku',
        'Alat Kebersihan',
        'Alat Keamanan',
        'Alat Multimedia',
        'Alat Laboratorium',
        'Alat Perkantoran',
        'Lainnya',
    ];

    const KONDISI_OPTIONS = ['Baik', 'Sedang', 'Rusak Ringan', 'Rusak Berat'];

    // RELATIONSHIPS
    public function ruang()
    {
        return $this->belongsTo(SarprasRuang::class, 'sarpras_ruang_id');
    }

    // SCOPES
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByJenis($query, $jenis)
    {
        return $query->where('jenis', $jenis);
    }
}
