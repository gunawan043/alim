<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RecruitmentProfile extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'nik', 'no_kk', 'tempat_lahir', 'tanggal_lahir',
        'nama_ibu_kandung', 'golongan_darah', 'jenis_kelamin', 'agama',
        'status_perkawinan', 'no_hp', 'no_whatsapp', 'kontak_darurat',
        'hubungan_kontak_darurat', 'alamat_lengkap', 'rt_rw', 'kelurahan_desa',
        'kecamatan', 'kota_kabupaten', 'provinsi', 'kode_pos',
        'status', 'submitted_at', 'verified_by', 'verified_at'
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function educations()
    {
        return $this->hasMany(RecruitmentEducation::class);
    }

    public function workExperiences()
    {
        return $this->hasMany(RecruitmentWorkExperience::class);
    }

    public function skills()
    {
        return $this->hasMany(RecruitmentSkill::class);
    }

    public function trainings()
    {
        return $this->hasMany(RecruitmentTraining::class);
    }

    public function documents()
    {
        return $this->hasMany(RecruitmentDocument::class);
    }

    public function applications()
    {
        return $this->hasMany(RecruitmentApplication::class);
    }
}