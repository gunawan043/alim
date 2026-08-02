<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SuratKeluar extends Model
{
    use LogsDeletion;
    use SoftDeletes;

    protected $table = 'surat_keluar';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'school_id',
        'nomor_surat',
        'tanggal_surat',
        'tanggal_kirim',
        'tujuan',
        'perihal',
        'file_lampiran',
        'sifat',
        'penandatangan',
        'jabatan_penandatangan',
        'status',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'tanggal_kirim' => 'date',
    ];

    const SIFAT_RAHASIA = 'rahasia';

    const SIFAT_BIASA = 'biasa';

    const SIFAT_PENTING = 'penting';

    const SIFAT_OPTIONS = [
        self::SIFAT_RAHASIA => 'Rahasia',
        self::SIFAT_BIASA => 'Biasa',
        self::SIFAT_PENTING => 'Penting',
    ];

    const STATUS_DRAFT = 'draft';

    const STATUS_TERKIRIM = 'terkirim';

    const STATUS_DIBATALKAN = 'dibatalkan';

    const STATUS_OPTIONS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_TERKIRIM => 'Terkirim',
        self::STATUS_DIBATALKAN => 'Dibatalkan',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scopeBySchool($query, ?string $schoolId)
    {
        if ($schoolId) {
            return $query->where('school_id', $schoolId);
        }

        return $query;
    }

    public function scopeByStatus($query, ?string $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }

        return $query;
    }

    public function scopeBySifat($query, ?string $sifat)
    {
        if ($sifat) {
            return $query->where('sifat', $sifat);
        }

        return $query;
    }
}
