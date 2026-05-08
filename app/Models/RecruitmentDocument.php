<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RecruitmentDocument extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'recruitment_profile_id', 'jenis_dokumen', 'nama_dokumen',
        'file_path', 'file_size', 'file_extension', 'ringkasan_profesional',
        'tujuan_karir', 'keahlian_unggulan', 'pencapaian_utama',
        'is_public', 'is_primary', 'version', 'is_verified',
        'verified_by', 'verified_at', 'catatan'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'keahlian_unggulan' => 'array',
        'pencapaian_utama' => 'array',
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