<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TalentPool extends Model
{
    use HasFactory;

    protected $table = 'talent_pool';

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
        'user_id',
        'kategori',
        'status',
        'skor_potensi',
        'skor_kinerja',
        'kompetensi_unggulan',
        'area_pengembangan',
        'jabatan_target',
        'estimasi_siap_tahun',
        'tanggal_masuk_pool',
        'tanggal_keluar_pool',
        'catatan',
        'dinominasikan_oleh',
    ];

    protected $casts = [
        'tanggal_masuk_pool' => 'date',
        'tanggal_keluar_pool' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dinominasikanOleh()
    {
        return $this->belongsTo(User::class, 'dinominasikan_oleh');
    }

    public function getKategoriLabelAttribute()
    {
        return [
            'high_potential' => 'High Potential',
            'high_performer' => 'High Performer',
            'key_talent' => 'Key Talent',
            'emerging_talent' => 'Emerging Talent',
        ][$this->kategori] ?? $this->kategori;
    }

    public function getKategoriColorAttribute()
    {
        return [
            'high_potential' => 'primary',
            'high_performer' => 'success',
            'key_talent' => 'warning',
            'emerging_talent' => 'info',
        ][$this->kategori] ?? 'secondary';
    }

    public function getStatusLabelAttribute()
    {
        return [
            'aktif' => 'Aktif',
            'tidak_aktif' => 'Tidak Aktif',
            'dipromosikan' => 'Dipromosikan',
            'keluar' => 'Keluar',
        ][$this->status] ?? $this->status;
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }
}
