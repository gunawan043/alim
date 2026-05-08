<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SuccessionPlanKandidat extends Model
{
    use HasFactory;

    protected $table = 'succession_plan_kandidat';
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
        'succession_plan_id',
        'user_id',
        'kesiapan',
        'skor_kesiapan',
        'prioritas',
        'kekuatan',
        'area_pengembangan',
        'rencana_pengembangan',
        'status',
    ];

    public function successionPlan()
    {
        return $this->belongsTo(SuccessionPlan::class, 'succession_plan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getKesiapanLabelAttribute()
    {
        return [
            'siap_sekarang'   => 'Siap Sekarang',
            'siap_1_2_tahun'  => 'Siap 1-2 Tahun',
            'siap_3_5_tahun'  => 'Siap 3-5 Tahun',
        ][$this->kesiapan] ?? $this->kesiapan;
    }

    public function getKesiapanColorAttribute()
    {
        return [
            'siap_sekarang'   => 'success',
            'siap_1_2_tahun'  => 'warning',
            'siap_3_5_tahun'  => 'info',
        ][$this->kesiapan] ?? 'secondary';
    }
}
