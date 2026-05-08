<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RecruitmentApplication extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'recruitment_profile_id', 'recruitment_job_id', 'no_lamaran',
        'status', 'skor_administrasi', 'nilai_tes', 'nilai_wawancara',
        'nilai_akhir', 'ranking', 'tanggal_melamar', 'diproses_at',
        'selesai_at', 'catatan_pelamar', 'catatan_rekruter', 'feedback',
        'processed_by'
    ];

    protected $casts = [
        'tanggal_melamar' => 'date',
        'diproses_at' => 'datetime',
        'selesai_at' => 'datetime',
        'feedback' => 'array',
    ];

    // Relationships
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function recruitmentProfile()
    {
        return $this->belongsTo(RecruitmentProfile::class);
    }

    public function recruitmentJob()
    {
        return $this->belongsTo(RecruitmentJob::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function stages()
    {
        return $this->hasMany(RecruitmentApplicationStage::class);
    }

    public function currentStage()
    {
        return $this->belongsTo(RecruitmentPipelineStage::class, 'current_stage_id');
    }
}