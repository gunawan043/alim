<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RecruitmentTraining extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'recruitment_profile_id', 'jenis', 'nama_pelatihan', 'penyelenggara',
        'tingkat', 'tanggal_mulai', 'tanggal_selesai', 'durasi_jam',
        'memiliki_sertifikat', 'no_sertifikat', 'tanggal_sertifikat',
        'masa_berlaku', 'status_sertifikat', 'deskripsi_materi',
        'kompetensi_diperoleh', 'nilai', 'sertifikat_path', 'materi_path',
        'is_verified', 'verified_by', 'verified_at'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_sertifikat' => 'date',
        'masa_berlaku' => 'date',
        'memiliki_sertifikat' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'kompetensi_diperoleh' => 'array',
    ];

    // Relationships
    public function recruitmentProfile()
    {
        return $this->belongsTo(RecruitmentProfile::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}