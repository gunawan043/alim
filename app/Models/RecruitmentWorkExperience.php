<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RecruitmentWorkExperience extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'recruitment_profile_id', 'nama_perusahaan', 'jenis_perusahaan',
        'bidang_perusahaan', 'posisi_terakhir', 'status_kepegawaian',
        'tanggal_mulai', 'tanggal_selesai', 'is_saat_ini', 'lama_bekerja_bulan',
        'jobdesc', 'kompetensi_utama', 'pencapaian', 'gaji_terakhir',
        'gaji_periode', 'nama_atasan', 'kontak_atasan', 'email_atasan',
        'sertifikat_kerja_path', 'referensi_path', 'paklaring_path',
        'alasan_keluar', 'alasan_keluar_lainnya'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_saat_ini' => 'boolean',
        'kompetensi_utama' => 'array',
        'pencapaian' => 'array',
    ];

    // Relationships
    public function recruitmentProfile()
    {
        return $this->belongsTo(RecruitmentProfile::class);
    }
}