<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecruitmentDocument extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'recruitment_profile_id', 'jenis_dokumen', 'nama_dokumen',
        'file_path', 'external_id', 'external_url', 'file_size', 'file_extension',
        'ringkasan_profesional', 'tujuan_karir', 'keahlian_unggulan', 'pencapaian_utama',
        'is_public', 'is_primary', 'version', 'is_verified',
        'verified_by', 'verified_at', 'catatan', 'synced_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'synced_at' => 'datetime',
        'keahlian_unggulan' => 'array',
        'pencapaian_utama' => 'array',
    ];

    /**
     * Get URL dokumen — prioritas URL external, fallback ke storage local
     */
    public function getViewUrlAttribute(): string
    {
        if (! empty($this->external_url)) {
            return $this->external_url;
        }
        if (! empty($this->file_path)) {
            return asset('storage/'.$this->file_path);
        }

        return '#';
    }

    /**
     * Apakah dokumen ini dari sistem external
     */
    public function getIsExternalAttribute(): bool
    {
        return ! empty($this->external_id) && ! empty($this->external_url);
    }

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
