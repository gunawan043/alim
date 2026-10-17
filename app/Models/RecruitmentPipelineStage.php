<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruitmentPipelineStage extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'recruitment_pipeline_stages';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'recruitment_pipeline_id', 'nama_tahapan', 'urutan', 'deskripsi',
        'durasi_hari', 'warna', 'icon', 'is_wajib', 'kriteria_kelulusan',
        'form_penilaian', 'notification_template', 'email_template',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'durasi_hari' => 'integer',
        'is_wajib' => 'boolean',
        'kriteria_kelulusan' => 'array',
        'form_penilaian' => 'array',
    ];

    /**
     * Get the pipeline that owns this stage.
     */
    public function recruitmentPipeline()
    {
        return $this->belongsTo(RecruitmentPipeline::class);
    }

    /**
     * Get the applications in this stage.
     */
    public function applications()
    {
        return $this->hasMany(RecruitmentApplication::class, 'current_stage_id');
    }
}
