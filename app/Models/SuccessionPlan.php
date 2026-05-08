<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SuccessionPlan extends Model
{
    use HasFactory;

    protected $table = 'succession_plans';
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
        'jabatan_kunci',
        'unit_kerja',
        'pemegang_jabatan_id',
        'perkiraan_kekosongan',
        'urgensi',
        'status',
        'deskripsi_jabatan',
        'persyaratan_kompetensi',
        'catatan',
        'dibuat_oleh',
    ];

    protected $casts = [
        'perkiraan_kekosongan' => 'date',
    ];

    public function pemegangJabatan()
    {
        return $this->belongsTo(User::class, 'pemegang_jabatan_id');
    }

    public function dibuatOleh()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function kandidat()
    {
        return $this->hasMany(SuccessionPlanKandidat::class, 'succession_plan_id');
    }

    public function getUrgensiColorAttribute()
    {
        return [
            'rendah'  => 'secondary',
            'sedang'  => 'warning',
            'tinggi'  => 'danger',
            'kritis'  => 'dark',
        ][$this->urgensi] ?? 'secondary';
    }

    public function getStatusLabelAttribute()
    {
        return [
            'aktif'      => 'Aktif',
            'selesai'    => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
        ][$this->status] ?? $this->status;
    }
}
