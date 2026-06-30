<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RecruitmentPipeline extends Model
{
    use HasFactory, SoftDeletes,, LogsDeletion HasUuids;

    protected $table = 'recruitment_pipelines';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'recruitment_job_id', 'nama_tahapan', 'urutan', 'deskripsi',
        'durasi_hari', 'warna', 'icon', 'is_active', 'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
        'durasi_hari' => 'integer',
    ];

    /**
     * Get the job that owns this pipeline.
     */
    public function recruitmentJob()
    {
        return $this->belongsTo(RecruitmentJob::class);
    }

    /**
     * Get the stages for this pipeline.
     */
    public function stages()
    {
        return $this->hasMany(RecruitmentPipelineStage::class);
    }

    /**
     * Get the user who created this pipeline.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active pipelines.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get pipeline progress for a specific application.
     */
    public function getProgressForApplication($applicationId)
    {
        $totalStages = $this->stages()->count();
        $completedStages = RecruitmentApplicationStage::where('recruitment_application_id', $applicationId)
            ->whereIn('status', ['lolos', 'selesai'])
            ->count();

        return $totalStages > 0 ? round(($completedStages / $totalStages) * 100) : 0;
    }
}