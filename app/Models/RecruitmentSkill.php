<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RecruitmentSkill extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'recruitment_profile_id', 'kategori', 'nama_skill', 'level',
        'tahun_pengalaman', 'sumber', 'kemampuan_lisan', 'kemampuan_menulis',
        'sertifikasi_path', 'tanggal_sertifikasi', 'berlaku_sampai'
    ];

    protected $casts = [
        'tanggal_sertifikasi' => 'date',
        'berlaku_sampai' => 'date',
    ];

    // Relationships
    public function recruitmentProfile()
    {
        return $this->belongsTo(RecruitmentProfile::class);
    }
}