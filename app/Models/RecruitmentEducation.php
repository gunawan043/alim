<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RecruitmentEducation extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'recruitment_educations';

    protected $fillable = [
        'recruitment_profile_id', 'jenjang', 'nama_sekolah', 'jurusan',
        'fakultas', 'tahun_masuk', 'tahun_lulus', 'is_ijazah_ada',
        'no_ijazah', 'nilai_akhir', 'skala_nilai', 'ipk', 'predikat_kelulusan',
        'ijazah_path', 'transkrip_path', 'sertifikat_akreditasi_path',
        'is_verified', 'verified_by', 'verified_at', 'catatan_verifikasi'
    ];

    protected $casts = [
        'tahun_masuk' => 'integer',
        'tahun_lulus' => 'integer',
        'is_ijazah_ada' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
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