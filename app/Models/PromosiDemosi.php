<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PromosiDemosi extends Model
{
    use HasFactory;

    protected $table = 'promosi_demosi';

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
        'jenis',
        'jabatan_lama',
        'jabatan_baru',
        'unit_kerja_lama',
        'unit_kerja_baru',
        'nomor_sk',
        'tanggal_sk',
        'tmt',
        'alasan',
        'status',
        'disetujui_oleh',
        'disetujui_at',
        'catatan_persetujuan',
        'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal_sk' => 'date',
        'tmt' => 'date',
        'disetujui_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dibuatOleh()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function disetujuiOleh()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function getJenisLabelAttribute()
    {
        return $this->jenis === 'promosi' ? 'Promosi' : 'Demosi';
    }

    public function getJenisColorAttribute()
    {
        return $this->jenis === 'promosi' ? 'success' : 'danger';
    }

    public function getStatusLabelAttribute()
    {
        return [
            'draft' => 'Draft',
            'diajukan' => 'Diajukan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
        ][$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        return [
            'draft' => 'secondary',
            'diajukan' => 'warning',
            'disetujui' => 'success',
            'ditolak' => 'danger',
        ][$this->status] ?? 'secondary';
    }

    public function scopePromosi($query)
    {
        return $query->where('jenis', 'promosi');
    }

    public function scopeDemosi($query)
    {
        return $query->where('jenis', 'demosi');
    }
}
