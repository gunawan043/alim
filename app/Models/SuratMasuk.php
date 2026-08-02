<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SuratMasuk extends Model
{
    use LogsDeletion;
    use SoftDeletes;

    protected $table = 'surat_masuk';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'school_id',
        'nomor_surat',
        'tanggal_surat',
        'tanggal_diterima',
        'pengirim',
        'perihal',
        'file_lampiran',
        'sifat',
        'sifat_penyelesaian',
        'disposisi_to',
        'disposisi_catatan',
        'status',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'tanggal_diterima' => 'date',
    ];

    const SIFAT_RAHASIA = 'rahasia';

    const SIFAT_BIASA = 'biasa';

    const SIFAT_PENTING = 'penting';

    const SIFAT_OPTIONS = [
        self::SIFAT_RAHASIA => 'Rahasia',
        self::SIFAT_BIASA => 'Biasa',
        self::SIFAT_PENTING => 'Penting',
    ];

    const PENYELESAIAN_SEGERA = 'segera';

    const PENYELESAIAN_BIASA = 'biasa';

    const PENYELESAIAN_OPTIONS = [
        self::PENYELESAIAN_SEGERA => 'Segera',
        self::PENYELESAIAN_BIASA => 'Biasa',
    ];

    const STATUS_BARU = 'baru';

    const STATUS_DIDISPOSISI = 'didisposisi';

    const STATUS_SELESAI = 'selesai';

    const STATUS_OPTIONS = [
        self::STATUS_BARU => 'Baru',
        self::STATUS_DIDISPOSISI => 'Didisposisi',
        self::STATUS_SELESAI => 'Selesai',
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
