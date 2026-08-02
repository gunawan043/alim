<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruitmentApplicationStage extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'recruitment_application_id', 'recruitment_pipeline_stage_id', 'urutan', 'status',
        'jadwal_mulai', 'jadwal_selesai', 'lokasi', 'penilai_id',
        'tim_penilai', 'nilai', 'catatan', 'detail_penilaian',
        'hasil_path', 'dimulai_at', 'selesai_at',
    ];

    protected $casts = [
        'jadwal_mulai' => 'datetime',
        'jadwal_selesai' => 'datetime',
        'dimulai_at' => 'datetime',
        'selesai_at' => 'datetime',
        'tim_penilai' => 'array',
        'detail_penilaian' => 'array',
    ];

    // Relationships
    public function recruitmentApplication()
    {
        return $this->belongsTo(RecruitmentApplication::class);
    }

    public function recruitmentPipelineStage()
    {
        return $this->belongsTo(\App\Models\RecruitmentPipelineStage::class, 'recruitment_pipeline_stage_id');
    }

    public function penilai()
    {
        return $this->belongsTo(User::class, 'penilai_id');
    }
}
