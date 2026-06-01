<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

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
        'processed_by', 'status_akhir', 'nilai_praktikum'
    ];

    protected $casts = [
        'tanggal_melamar' => 'date',
        'diproses_at' => 'datetime',
        'selesai_at' => 'datetime',
        'feedback' => 'array',
        'nilai_tes_tulis' => 'decimal:2',
        'nilai_tes_praktikum' => 'decimal:2',
        'nilai_wawancara' => 'decimal:2',
        'nilai_akhir' => 'decimal:2',
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

    protected $appends = ['status_label', 'status_color', 'applied_jobs'];

    public function getStatusLabelAttribute(): string
    {
        // Stage label based on application status
        $stageMap = [
            'menunggu_seleksi'    => 'Menunggu Seleksi',
            'seleksi_administrasi'=> 'Seleksi Administrasi',
            'lolos_administrasi'   => 'Lolos Administrasi',
            'tidak_lolos_administrasi' => 'Tidak Lolos',
            'tes_tertulis'        => 'Hari Tes',
            'lolos_tes'            => 'Lolos Tes',
            'tidak_lolos_tes'       => 'Tidak Lolos Tes',
            'wawancara'            => 'Wawancara',
            'lolos_wawancara'      => 'Lolos Wawancara',
            'tidak_lolos_wawancara'=> 'Tidak Lolos Wawancara',
            'diterima'             => 'Diterima',
            'ditolak'              => 'Ditolak',
        ];
        return $stageMap[$this->status] ?? Str::title(str_replace('_', ' ', $this->status));
    }

    public function getStatusColorAttribute(): string
    {
        return match (true) {
            $this->status === 'diterima', $this->status === 'lolos_administrasi', $this->status === 'lolos_tes', $this->status === 'lolos_wawancara' => 'success',
            $this->status === 'ditolak', $this->status === 'tidak_lolos_administrasi', $this->status === 'tidak_lolos_tes', $this->status === 'tidak_lolos_wawancara' => 'danger',
            $this->status === 'seleksi_administrasi', $this->status === 'tes_tertulis', $this->status === 'wawancara', $this->status === 'menunggu_seleksi' => 'warning',
            $this->status === 'penawaran_kerja' => 'primary',
            default => 'secondary',
        };
    }

    public function getStatusAkhirLabelAttribute(): string
    {
        return match ($this->status_akhir) {
            'diterima' => 'DITERIMA',
            'ditolak'  => 'DITOLAK',
            'cadangan' => 'CADANGAN',
            default    => 'MENUNGGU',
        };
    }

    public function getRataRataNilaiAttribute(): ?float
    {
        $values = array_filter([
            $this->nilai_tes,
            $this->nilai_praktikum,
            $this->nilai_wawancara,
        ]);
        return count($values) > 0 ? round(array_sum($values) / count($values), 2) : null;
    }

    public function getTahapanSelesaiAttribute(): int
    {
        return $this->stages()->whereIn('status', ['lolos', 'selesai'])->count();
    }

    public function getTotalTahapanAttribute(): int
    {
        return $this->stages()->count();
    }
}